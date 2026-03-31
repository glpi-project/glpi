// eslint-disable-next-line no-undef
module.exports = {
    input: {
        path: "js",
        include: ["**/*.vue"],
        parserOptions: {
            overrideDefaultKeywords: true,
            mapping: {
                simple: ["__"],
                plural: ["_n"],
                ctxPlural: ["_nx"],
                ctx: ["_x"]
            }
        }
    },
    output: {
        path: "./",
        potPath: "locales/vue.pot",
        locales: [],
        splitJson: false,
        linguas: false,
        flat: true
    },
};
