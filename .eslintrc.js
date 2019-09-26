module.exports = {
    "env": {
        "browser": true,
        jquery: true,
    },
    "parserOptions": {
        "ecmaVersion": 6,
        "sourceType": "module",
    },
    "extends": [
        // "eslint:recommended",
        "plugin:vue/recommended",
    ],
    "rules": {
        "semi": [
            "error",
            "always"
        ],
        "vue/html-indent": "off",

        // "vue/html-indent": ["error", 4, {
        //     "attribute": 0,
        //     "baseIndent": 1,
        //     "closeBracket": 0,
        //     "alignAttributesVertically": false,
        //     "ignores": []
        // }]
    }
};
