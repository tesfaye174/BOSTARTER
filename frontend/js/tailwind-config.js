// ===== TAILWIND CSS CONFIGURATION ===== 
// Configurazione Tailwind CSS personalizzata
tailwind.config = {
    darkMode: 'class',
    theme: {
        extend: {
            // Palette colori personalizzata
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
            // Font personalizzati
            fontFamily: {
                'sans': ['Inter', 'system-ui', 'sans-serif'],
                'brand': ['Pacifico', 'cursive']
            },
            // Border radius personalizzato
            borderRadius: {
                'button': '8px',
            },
            // Animazioni personalizzate
            animation: {
                'fade-in': 'fadeIn 0.5s ease-out',
                'fade-out': 'fadeOut 0.3s ease-in',
                'slide-up': 'slideUp 0.5s ease-out',
                'slide-down': 'slideDown 0.3s ease-out',
                'slide-in': 'slideIn 0.5s ease-out',
                'slide-in-right': 'slideInRight 0.3s ease-out',
                'slide-out-right': 'slideOutRight 0.3s ease-in',
                'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'bounce-slow': 'bounce 2s infinite',
                'shake': 'shake 0.5s ease-in-out',
                'scale-in': 'scaleIn 0.2s ease-out',
                'scale-out': 'scaleOut 0.2s ease-in'
            },
            // Keyframes per animazioni
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' }
                },
                fadeOut: {
                    '0%': { opacity: '1' },
                    '100%': { opacity: '0' }
                },
                slideUp: {
                    '0%': { transform: 'translateY(20px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' }
                },
                slideDown: {
                    '0%': { transform: 'translateY(-10px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' }
                },
                slideIn: {
                    '0%': { transform: 'translateX(-20px)', opacity: '0' },
                    '100%': { transform: 'translateX(0)', opacity: '1' }
                },
                slideInRight: {
                    '0%': { transform: 'translateX(100%)', opacity: '0' },
                    '100%': { transform: 'translateX(0)', opacity: '1' }
                },
                slideOutRight: {
                    '0%': { transform: 'translateX(0)', opacity: '1' },
                    '100%': { transform: 'translateX(100%)', opacity: '0' }
                },
                shake: {
                    '0%, 100%': { transform: 'translateX(0)' },
                    '25%': { transform: 'translateX(-5px)' },
                    '75%': { transform: 'translateX(5px)' }
                },
                scaleIn: {
                    '0%': { transform: 'scale(0.95)', opacity: '0' },
                    '100%': { transform: 'scale(1)', opacity: '1' }
                },
                scaleOut: {
                    '0%': { transform: 'scale(1)', opacity: '1' },
                    '100%': { transform: 'scale(0.95)', opacity: '0' }
                }
            },
            // Spacing personalizzato
            spacing: {
                '18': '4.5rem',
                '88': '22rem'
            },
            // Box shadow personalizzate
            boxShadow: {
                'card': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
                'card-hover': '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
                'button': '0 2px 4px rgba(0, 0, 0, 0.1)',
                'button-hover': '0 4px 8px rgba(0, 0, 0, 0.15)'
            }
        }
    }
};
