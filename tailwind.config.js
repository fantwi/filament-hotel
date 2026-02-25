export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./app/Filament/**/*.php",
  ],
  corePlugins: {
    preflight: false, // fix for large icons and broken layout
  },
  theme: {
    extend: {},
  },
  plugins: [],
}