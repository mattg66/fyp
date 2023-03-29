/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./node_modules/@alfiejones/flowbite-react/**/*.js",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {},
  },
  darkMode: 'class',
  plugins: [
    require('flowbite/plugin')
  ],
  variants: {
    extend: {
      display: ["group-hover"],
    },
  },
}
