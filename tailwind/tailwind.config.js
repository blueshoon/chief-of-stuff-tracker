/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './public/**/*.php',
    './src/**/*.php',
  ],
  theme: {
    extend: {
      colors: {
        navy: {
          50:  '#eef0f7',
          100: '#d4d8e8',
          200: '#9aa1c2',
          300: '#5b6595',
          400: '#2c3870',
          500: '#000042',
          600: '#00003a',
          700: '#00002e',
          800: '#000022',
          900: '#000018',
        },
        rust: {
          50:  '#fbf1ec',
          100: '#f3d9cb',
          200: '#e2ad94',
          300: '#cf8160',
          400: '#a35a37',
          500: '#864322',
          600: '#6f371b',
          700: '#582b15',
          800: '#411f0f',
          900: '#2a1409',
        },
        cream: '#fbf7f2',
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'Segoe UI', 'sans-serif'],
        display: ['Fraunces', 'Georgia', 'serif'],
      },
      boxShadow: {
        card: '0 10px 40px -12px rgba(0, 0, 66, 0.35)',
        glow: '0 0 0 4px rgba(134, 67, 34, 0.25)',
      },
      keyframes: {
        'pulse-ring': {
          '0%':   { boxShadow: '0 0 0 0 rgba(134, 67, 34, 0.55)' },
          '70%':  { boxShadow: '0 0 0 12px rgba(134, 67, 34, 0)' },
          '100%': { boxShadow: '0 0 0 0 rgba(134, 67, 34, 0)' },
        },
        'slide-up': {
          '0%':   { transform: 'translateY(20px)', opacity: '0' },
          '100%': { transform: 'translateY(0)',    opacity: '1' },
        },
      },
      animation: {
        'pulse-ring': 'pulse-ring 1.8s cubic-bezier(0.4, 0, 0.6, 1) infinite',
        'slide-up':   'slide-up 0.4s cubic-bezier(0.16, 1, 0.3, 1)',
      },
    },
  },
  plugins: [],
};
