module.exports = {
  apps: [
    {
      name: "aidara",
      script: ".output/server/index.mjs",
      exec_mode: "fork",
      env: {
        HOST: "127.0.0.1",
        PORT: 3001,
        NITRO_PORT: 3001,
        NODE_ENV: "production"
      }
    }
  ]
};
