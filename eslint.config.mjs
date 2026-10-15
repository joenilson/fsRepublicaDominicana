// Revisión mínima del JavaScript del plugin: npx eslint Assets/JS
const browser = {
  window: 'readonly', document: 'readonly', console: 'readonly', setTimeout: 'readonly',
  $: 'readonly', jQuery: 'readonly', DOMParser: 'readonly', MutationObserver: 'readonly',
};

export default [
  {
    files: ['Assets/JS/**/*.js'],
    languageOptions: {
      ecmaVersion: 2022,
      sourceType: 'script',
      // executeModal lo define CommonModals.js y lo usan los demás archivos
      globals: { ...browser, executeModal: 'readonly' },
    },
    rules: {
      'no-undef': 'error',
      // las funciones de nivel superior se usan desde atributos onclick/onchange generados por PHP
      'no-unused-vars': ['warn', { vars: 'local', args: 'none' }],
      'no-redeclare': 'error',
      'eqeqeq': ['warn', 'always'],
    },
  },
  {
    files: ['Assets/JS/CommonModals.js'],
    languageOptions: { globals: browser },
    rules: { 'no-redeclare': 'off' },
  },
];
