const { defineConfig } = require("@vue/cli-service");
module.exports = defineConfig({
  transpileDependencies: true,
  productionSourceMap: false,
  devServer: {
    proxy: {
      "/public": {
        target: "http://kennections-player.test",
        ws: true,
        changeOrigin: true,
      },
    },
  },
});
