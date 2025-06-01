tailwind.config = {
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: "#E91E63",
                    50: "#FCE4EC",
                    100: "#F8BBD9",
                    200: "#F48FB1",
                    300: "#F06292",
                    400: "#EC407A",
                    500: "#E91E63",
                    600: "#D81B60",
                    700: "#C2185B",
                    800: "#AD1457",
                    900: "#880E4F",
                    950: "#560E2E"
                },
                secondary: {
                    DEFAULT: "#9C27B0",
                    50: "#F3E5F5",
                    100: "#E1BEE7",
                    200: "#CE93D8",
                    300: "#BA68C8",
                    400: "#AB47BC",
                    500: "#9C27B0",
                    600: "#8E24AA",
                    700: "#7B1FA2",
                    800: "#6A1B9A",
                    900: "#4A148C",
                    950: "#2E0A54"
                }
            },
            fontFamily: {
                'sans': ['Inter', 'system-ui', 'sans-serif'],
                'serif': ['Playfair Display', 'serif'],
                'brand': ['Pacifico', 'cursive']
            },
            animation: {
                'fade-in': 'fadeIn 0.6s ease-out',
                'slide-up': 'slideUp 0.6s ease-out',
                'scale-in': 'scaleIn 0.4s ease-out',
                'float': 'float 6s ease-in-out infinite'
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' }
                },
                slideUp: {
                    '0%': { transform: 'translateY(30px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' }
                },
                scaleIn: {
                    '0%': { transform: 'scale(0.9)', opacity: '0' },
                    '100%': { transform: 'scale(1)', opacity: '1' }
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0px) rotate(0deg)' },
                    '50%': { transform: 'translateY(-20px) rotate(3deg)' }
                }
            }
        }
    }
};
