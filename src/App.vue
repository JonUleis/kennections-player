<template>
  <div class="game">
    <header class="header">
      <h1>Kennections Player</h1>
      <p>
        Play the new slideshow-style
        <a href="https://www.mentalfloss.com/kennections" target="_blank"
          >Kennections</a
        >
        games introduced in March 2022 interactively!
      </p>
      <p>
        Note that the new Kennections games no longer specify alternate answers
        like the previous interactive version (eg. "steaks", "types of steak"),
        but some fuzzy matching will be performed while answering.
      </p>
      <p>
        <label>
          <input type="checkbox" v-model="showBlanks" /> Show — — — — answer
          blanks before typing
        </label>
      </p>
    </header>
    <div class="loading" v-if="!puzzleJson.title">
      <span v-if="this.latest">Loading Puzzle {{ this.puzzle }}...</span>
      <span v-else>Connecting...</span>
    </div>
    <div class="content" v-else>
      <h1>{{ puzzleJson.title }}</h1>
      <h2>
        by
        <a :href="puzzleJson.authors[0].link" target="_blank">
          {{ puzzleJson.authors[0].name }}
        </a>
        &bull;
        {{ puzzleJson.createdAt }}
      </h2>

      <label v-for="(e, i) in inputs" :key="`q-${i}`" :readonly="correct[i]">
        <span v-if="i < 5">
          <span class="number">{{ i + 1 }}) </span>
          <span v-html="questions[i]" />
        </span>
        <b v-else>What's the Kennection?</b>

        <div class="input">
          <div class="answer" v-if="submitted || correct[i]">
            <span class="incorrect" v-if="!correct[i]">
              {{
                inputs[i]
                  ? `You answered: ${inputs[i]}.`
                  : "You did not answer."
              }}
            </span>
            <span class="correct">
              {{ correct[i] ? "Correct:" : "The correct answer is:" }}
              {{ answers[i] }}
            </span>
          </div>
          <input
            v-else
            v-model="inputs[i]"
            @input="$event.target.composing = false"
            @keyup="changed(i)"
            @change="changed(i)"
            @blur="blur(i, $event)"
            @keyup.enter="i == 5 ? solve() : null"
            :placeholder="showBlanks ? blanks[i] : ''"
            autocomplete="cancel"
          />
        </div>
      </label>

      <div class="buttons" v-if="!submitted">
        <button @click="solve">I GIVE UP</button>
        <button @click="solve">SUBMIT</button>
      </div>

      <img
        v-if="image"
        class="image"
        :src="`${image.host}${image.path}`"
        :style="!submitted ? 'height: 0px' : null"
        :alt="!submitted ? null : image.alt"
      />
      <span v-if="submitted && (image?.credit || image?.caption)">
        {{ image.caption }} {{ image.credit && image.caption ? "/" : "" }}
        {{ image.credit }}
      </span>
    </div>
    <nav class="nav" v-if="puzzleJson.title">
      <span>Kennections Games</span>
      <ul v-if="latest % 10">
        <li v-for="i in latest % 10" :key="`nav-${i}`">
          <a
            @click="puzzle = latest - i + 1"
            :class="{ active: puzzle == latest - i + 1 }"
            >{{ latest - i + 1 }}</a
          >
        </li>
      </ul>
      <ul v-for="list in navRows" :key="`nav-${list}`">
        <li v-for="i in 10" :key="`nav-${i}`">
          <a
            @click="puzzle = (list + 1) * 10 - i + 1"
            :class="{ active: puzzle == (list + 1) * 10 - i + 1 }"
            >{{ list == 0 && i > 1 ? "0" : "" }}{{ (list + 1) * 10 - i + 1 }}</a
          >
        </li>
      </ul>
    </nav>
    <footer class="footer">
      Kennections Player by
      <a href="https://twitter.com/MovingToTheSun" target="_blank">Jon Uleis</a
      >. Source code on
      <a href="https://github.com/JonUleis/kennections-player" target="_blank"
        >GitHub</a
      >.
      <br />
      Games are loaded from
      <a href="https://www.mentalfloss.com/kennections" target="_blank"
        >Mental Floss</a
      >
      and not stored on this server.
    </footer>
  </div>
</template>

<script>
import axios from "axios";
import pluralize from "pluralize";

export default {
  name: "App",
  data() {
    return {
      puzzle: 0,
      latest: 0,
      navRows: 0,
      puzzleJson: {},
      inputs: Array(6),
      correct: Array(6),
      questions: Array(5),
      blanks: Array(5),
      answers: Array(6),
      showBlanks: false,
      submitted: false,
      image: null,
    };
  },
  mounted() {
    axios.post(`${process.env.VUE_APP_PHP}php/`).then((res) => {
      if (res.data) {
        // get latest puzzle number
        this.latest =
          res.data.template.authorPageInfo.articles[0].title.split("#")[1];
        this.navRows = [...Array(Math.floor(this.latest / 10)).keys()]
          .slice()
          .reverse();
        this.puzzle = this.latest;
      }
    });
  },
  watch: {
    puzzle() {
      this.getPuzzle();
    },
  },
  methods: {
    changed(el) {
      if (el == 5) {
        // don't auto-submit kennection
        if (!this.submitted) {
          return false;
          // if submitted, match kennection if 3+ letters long word is contained in real kennection
        } else {
          const answerWords = this.inputs[el]?.split(" ");
          answerWords?.every((word) => {
            if (
              this.fuzzy(word).length >= 3 &&
              this.fuzzy(this.answers[el]).includes(this.fuzzy(word))
            ) {
              this.correct[el] = true;
              return false;
            }
            return true;
          });
        }
      } else if (
        // check if input contains the whole answer
        this.inputs[el] &&
        this.fuzzy(this.inputs[el])?.includes(this.fuzzy(this.answers[el]))
      ) {
        this.correct[el] = true;
        // focus next unsolved field
        this.$nextTick(() => {
          document.querySelector("label[readonly] ~ label input")?.focus();
        });
      }
    },
    blur(el, event) {
      if (!this.correct[el]) {
        this.inputs[el] = event.target.value;
        this.changed(el);
      }
    },
    solve() {
      this.submitted = true;
      for (let i = 0; i < this.inputs.length; i++) {
        this.changed(i);
      }
    },
    getPuzzle() {
      this.puzzleJson = {};
      this.inputs = Array(6);
      this.correct = Array(6);
      this.submitted = false;
      this.image = null;
      axios
        .post(`${process.env.VUE_APP_PHP}php/`, { puzzle: this.puzzle })
        .then((res) => {
          if (res.data) {
            this.puzzleJson = res.data.template;

            if (this.puzzleJson.body[1]?.type === "iframeEmbed") {
              // for puzzle 93 and later with new interactive iframe
              const data = this.puzzleJson.body[1].html;
              this.questions = this.buildArray(
                data,
                "lt;h3(.*?).(.*?) ",
                "&amp(.*?)lt;/h3"
              );
              this.answers = this.buildArray(
                data,
                "(ul&amp;amp;amp;amp;gt;&amp;amp;amp;amp;lt;li&amp;amp;amp;amp;gt;|ul&amp;gt;&amp;lt;li&amp;gt;)",
                "(&amp;amp;amp;|&amp;lt;/)"
              );
              this.answers = this.answers.map((answer) => answer.toUpperCase());
              this.blanks = this.answers.map((answer) =>
                answer.replace(/ /g, "// ").replace(/[^ /]/g, "— ")
              );
              this.blanks[5] = "";
            } else {
              // find index of q1
              let qIndex = this.puzzleJson.body.findIndex((el) => {
                return el.html?.includes("1.");
              });
              if (qIndex < 0) qIndex = 1;

              // find index of q1 in the answers
              let aIndex =
                this.puzzleJson.body.length -
                this.puzzleJson.body
                  .slice(0)
                  .reverse()
                  .findIndex((el) => {
                    return el.html?.includes("1.");
                  });
              if (aIndex < 0 || aIndex >= this.puzzleJson.body.length) {
                aIndex =
                  this.puzzleJson.body.findIndex((el) => {
                    return el.text?.includes("ANSWERS");
                  }) + 3;
              }

              this.questions = [
                this.clean(this.puzzleJson.body[qIndex].html),
                this.clean(this.puzzleJson.body[qIndex + 2].html),
                this.clean(this.puzzleJson.body[qIndex + 4].html),
                this.clean(this.puzzleJson.body[qIndex + 6].html),
                this.clean(this.puzzleJson.body[qIndex + 8].html),
              ];
              this.blanks = [
                this.clean(this.puzzleJson.body[qIndex + 1].html),
                this.clean(this.puzzleJson.body[qIndex + 3].html),
                this.clean(this.puzzleJson.body[qIndex + 5].html),
                this.clean(this.puzzleJson.body[qIndex + 7].html),
                this.clean(this.puzzleJson.body[qIndex + 9].html),
              ];
              this.answers = [
                this.clean(this.puzzleJson.body[aIndex].html),
                this.clean(this.puzzleJson.body[aIndex + 2].html),
                this.clean(this.puzzleJson.body[aIndex + 4].html),
                this.clean(this.puzzleJson.body[aIndex + 6].html),
                this.clean(this.puzzleJson.body[aIndex + 8].html),
                this.clean(
                  // find kennection (last element with .html)
                  this.puzzleJson.body
                    .slice(0)
                    .reverse()
                    .find((el) => {
                      return el.html;
                    }).html
                ),
              ];
              this.image = this.puzzleJson.body
                .slice(0)
                .reverse()
                .find((el) => {
                  return el.image;
                })?.image;

              // clean up Kennections #1
              if (this.puzzle == 1) {
                aIndex = aIndex - 1;
                this.answers = [
                  this.cleanK1(this.puzzleJson.body[aIndex].html),
                  this.cleanK1(this.puzzleJson.body[aIndex + 1].html),
                  this.cleanK1(this.puzzleJson.body[aIndex + 2].html),
                  this.cleanK1(this.puzzleJson.body[aIndex + 3].html),
                  this.cleanK1(this.puzzleJson.body[aIndex + 4].html),
                  this.answers[5].replace("ANSWER:", ""),
                ];
              }

              this.questions.forEach((q, i) => {
                this.questions[i] = q.replace(/^([1-5])\.[\s]*/, "");
              });
            }

            // debug - put answers into fields
            // this.inputs = this.answers.slice(0);
          }
        });
    },
    clean(text) {
      // strip HTML from q & a's
      return text
        ?.replace(/(<([^>]+)>)/gi, "")
        .replace(/&#[0-9]+;/gi, "")
        .replace(/&amp;amp;/g, "&")
        .replace(/&quot;/g, '"')
        .trim();
    },
    cleanK1(text) {
      // clean up Kennections #1
      return text.split("<br/><br/>")[1].split("</p>")[0];
    },
    fuzzy(text) {
      return pluralize
        .singular(text)
        ?.toLowerCase()
        .trim()
        .replace(/^a /, "")
        .replace(/^the /, "")
        .replace(/[^a-z0-9]/gi, "");
    },
    buildArray(text, startMarker, endMarker) {
      const regexPattern = new RegExp(`${startMarker}(.*?)${endMarker}`, "g");
      const matches = text.match(regexPattern) || [];
      return matches.map((match) =>
        this.clean(
          match.replace(new RegExp(`${startMarker}|${endMarker}`, "g"), "")
        )
      );
    },
  },
};
</script>

<style lang="scss">
@import "main.scss";
</style>
