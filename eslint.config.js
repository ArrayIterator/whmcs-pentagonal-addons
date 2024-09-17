const tsParser = require("@typescript-eslint/parser");

module.exports = [
    {
        ignores: [
            "node_modules/**/*",
            "dist/**/*",
            "js/**/*",
            "*.js"
        ],
        files: [
            "ui/js/**/*.ts",
            "webpack.config.js",
            "postcss.config.js"
        ],
        languageOptions: {
            parser: tsParser,
            parserOptions: {
                project: "./tsconfig.json",
                ecmaVersion: 2020,
                sourceType: "script"
            }
        },
        plugins: {
            "@typescript-eslint": require("@typescript-eslint/eslint-plugin"),
            "import-newlines": require("eslint-plugin-import-newlines")
        },
        rules: {
            semi: "off",
            "import-newlines/enforce": [
                "error",
                {
                    items: 1
                }
            ],
            "no-eval": "error",
            "no-regex-spaces": "warn",
            "spaced-comment": "warn",
            "brace-style": "error",
            "key-spacing": [
                "error",
                {
                    beforeColon: true,
                    afterColon: true
                }
            ],
            "array-bracket-spacing": [
                "error",
                "never"
            ],
            "block-spacing": [
                "error",
                "always"
            ],
            "comma-spacing": [
                "error",
                {
                    before: false,
                    after: true
                }
            ],
            "comma-style": [
                "error",
                "last"
            ],
            indent: [
                "error",
                4,
                {
                    SwitchCase: 1
                }
            ],
            "no-console": "off",
            "n/no-path-concat": "off",
            "object-curly-spacing": "off",
            "no-multiple-empty-lines": [
                "error",
                {
                    max: 1
                }
            ],
            "object-property-newline": [
                "error",
                {
                    allowAllPropertiesOnSameLine: false,
                    allowMultiplePropertiesPerLine: false
                }
            ],
            "object-curly-newline": [
                "error",
                {
                    ObjectExpression: {
                        multiline: true,
                        minProperties: 1
                    },
                    ObjectPattern: {
                        multiline: true,
                        minProperties: 1
                    },
                    ImportDeclaration: {
                        multiline: true,
                        minProperties: 2
                    },
                    ExportDeclaration: {
                        multiline: true,
                        minProperties: 2
                    }
                }
            ],
            "@typescript-eslint/explicit-function-return-type": "error",
            "@typescript-eslint/explicit-member-accessibility": "error",
            "@typescript-eslint/ban-ts-comment": "error",
            "@typescript-eslint/ban-types": "off", // Disable the rule if not found
            "no-array-constructor": "off",
            "@typescript-eslint/no-array-constructor": "error",
            "@typescript-eslint/no-duplicate-enum-values": "error",
            "@typescript-eslint/no-explicit-any": "off",
            "@typescript-eslint/no-extra-non-null-assertion": "error",
            "no-loss-of-precision": "off",
            "@typescript-eslint/no-loss-of-precision": "error",
            "@typescript-eslint/no-misused-new": "error",
            "@typescript-eslint/no-namespace": "error",
            "@typescript-eslint/no-non-null-asserted-optional-chain": "error",
            "@typescript-eslint/no-this-alias": "error",
            "@typescript-eslint/no-unnecessary-type-constraint": "error",
            "@typescript-eslint/no-unsafe-declaration-merging": "error",
            "no-unused-vars": "off",
            "@typescript-eslint/no-unused-vars": [
                "error",
                {
                    argsIgnorePattern: "^_",
                    varsIgnorePattern: "^_",
                    caughtErrorsIgnorePattern: "^_"
                }
            ],
            "@typescript-eslint/no-var-requires": "error",
            "@typescript-eslint/prefer-as-const": "error",
            "@typescript-eslint/triple-slash-reference": "error"
        }
    }
];