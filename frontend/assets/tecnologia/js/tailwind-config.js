// Tailwind CSS Configuration for Tecnologia Page

tailwind.config = {
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: "#3176FF",
                    50: "#EBF2FF",
                    100: "#D6E4FF",
                    200: "#B3CCFF",
                    300: "#80AAFF",
                    400: "#4D88FF",
                    500: "#3176FF",
                    600: "#1A5EFF",
                    700: "#0A47E6",
                    800: "#0837B8",
                    900: "#062A8A",
                    950: "#041C5C"
                },
                secondary: {
                    DEFAULT: "#FF6B35",
                    50: "#FFF3F0",
                    100: "#FFE6E0",
                    200: "#FFCCC0",
                    300: "#FF9980",
                    400: "#FF8055",
                    500: "#FF6B35",
                    600: "#FF4500",
                    700: "#E03A00",
                    800: "#B32F00",
                    900: "#802200",
                    950: "#4D1500"
                }
            },
            fontFamily: {
                'sans': ['Inter', 'system-ui', 'sans-serif'],
                'brand': ['Pacifico', 'cursive']
            },
            animation: {
                'fade-in': 'fadeIn 0.6s ease-out',
                'slide-up': 'slideUp 0.6s ease-out',
                'scale-in': 'scaleIn 0.4s ease-out'
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
                }
            }
        }
    }
};
