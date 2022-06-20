<template>
  <div class="loading" v-if="!puzzleJson.title">
    <span v-if="this.latest">Loading Puzzle {{ this.puzzle }}...</span>
    <span v-else>Connecting...</span>
  </div>
  <div class="game" v-else>
    <h1>{{ puzzleJson.title }}</h1>
    <h2>
      by
      <a :href="puzzleJson.authors[0].link" target="_blank">
        {{ puzzleJson.authors[0].name }}
      </a>
    </h2>

    <label>
      <span v-html="questions[0]" />
      <input type="text" v-model="input1" />
    </label>
    <label>
      <span v-html="questions[1]" />
      <input type="text" v-model="input2" />
    </label>
    <label>
      <span v-html="questions[2]" />
      <input type="text" v-model="input3" />
    </label>
    <label>
      <span v-html="questions[3]" />
      <input type="text" v-model="input4" />
    </label>
    <label>
      <span v-html="questions[4]" />
      <input type="text" v-model="input5" />
    </label>

    <label>
      <b>What's the Kennection?</b>
      <input type="text" v-model="inputKennection" />
    </label>
  </div>
  <nav class="nav">
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
</template>

<script>
import axios from "axios";

export default {
  name: "App",
  data() {
    return {
      puzzle: 0,
      latest: 0,
      puzzleJson: {},
      input1: "",
      input2: "",
      input3: "",
      input4: "",
      input5: "",
      inputKennection: "",
      questions: Array(5),
      answers: Array(5),
      kennection: "",
      navRows: 0,
    };
  },
  mounted() {
    axios.post(`${process.env.VUE_APP_PHP}php/`).then((res) => {
      if (res.data) {
        this.latest =
          res.data.template.feedSection.articles[0].title.split("#")[1];
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
    getPuzzle() {
      this.puzzleJson = {};
      axios
        .post(`${process.env.VUE_APP_PHP}php/`, { puzzle: this.puzzle })
        .then((res) => {
          if (res.data) {
            this.puzzleJson = res.data.template;

            // find index of q1
            let qIndex = this.puzzleJson.body.findIndex((el) => {
              return el.html?.includes("1.");
            });
            // find index of q1 in the answers
            let aIndex =
              this.puzzleJson.body.length -
              this.puzzleJson.body
                .slice(0)
                .reverse()
                .findIndex((el) => {
                  return el.html?.includes("1.");
                });

            this.questions = [
              this.clean(this.puzzleJson.body[qIndex].html),
              this.clean(this.puzzleJson.body[qIndex + 2].html),
              this.clean(this.puzzleJson.body[qIndex + 4].html),
              this.clean(this.puzzleJson.body[qIndex + 6].html),
              this.clean(this.puzzleJson.body[qIndex + 8].html),
            ];
            this.answers = [
              this.clean(this.puzzleJson.body[aIndex].html),
              this.clean(this.puzzleJson.body[aIndex + 2].html),
              this.clean(this.puzzleJson.body[aIndex + 4].html),
              this.clean(this.puzzleJson.body[aIndex + 6].html),
              this.clean(this.puzzleJson.body[aIndex + 8].html),
            ];
            this.kennection = this.clean(
              this.puzzleJson.body
                .slice(0)
                .reverse()
                .find((el) => {
                  return el.html;
                }).html
            );

            // clean up Kennections #1
            if (this.puzzle == 1) {
              this.kennection = this.kennection.replace("ANSWER:", "");
              aIndex = aIndex - 1;
              this.answers = [
                this.cleanK1(this.puzzleJson.body[aIndex].html),
                this.cleanK1(this.puzzleJson.body[aIndex + 1].html),
                this.cleanK1(this.puzzleJson.body[aIndex + 2].html),
                this.cleanK1(this.puzzleJson.body[aIndex + 3].html),
                this.cleanK1(this.puzzleJson.body[aIndex + 4].html),
              ];
            }

            // debug
            this.input1 = this.answers[0];
            this.input2 = this.answers[1];
            this.input3 = this.answers[2];
            this.input4 = this.answers[3];
            this.input5 = this.answers[4];
            this.inputKennection = this.kennection;
          }
        });
    },
    clean(text) {
      return text?.replace(/(<([^>]+)>)/gi, "").trim();
    },
    cleanK1(text) {
      return text.split("<br/><br/>")[1].split("</p>")[0];
    },
  },
};
</script>

<style lang="scss">
@import "main.scss";
</style>
