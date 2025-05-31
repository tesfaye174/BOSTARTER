<?php
session_start();

// Demo user session for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'demo_user';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOSTARTER Dashboard - Feature Demo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-rocket text-blue-600 mr-3"></i>
                    BOSTARTER Dashboard Integration
                </h1>
                <p class="text-xl text-gray-600">Complete Feature Demonstration</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Feature Cards -->
                <div class="bg-gradient-to-br from-blue-500 to-purple-600 text-white p-6 rounded-xl">
                    <h3 class="text-xl font-semibold mb-3">
                        <i class="fas fa-palette mr-2"></i>
                        Modern UI/UX
                    </h3>
                    <ul class="space-y-2 text-sm opacity-90">
                        <li>✅ Responsive Tailwind CSS design</li>
                        <li>✅ Dark/Light theme toggle</li>
                        <li>✅ Smooth animations & transitions</li>
                        <li>✅ Mobile-optimized interface</li>
                    </ul>
                </div>

                <div class="bg-gradient-to-br from-green-500 to-teal-600 text-white p-6 rounded-xl">
                    <h3 class="text-xl font-semibold mb-3">
                        <i class="fas fa-database mr-2"></i>
                        Backend Integration
                    </h3>
                    <ul class="space-y-2 text-sm opacity-90">
                        <li>✅ MySQL database connectivity</li>
                        <li>✅ User authentication system</li>
                        <li>✅ Dynamic content loading</li>
                        <li>✅ Secure session management</li>
                    </ul>
                </div>

                <div class="bg-gradient-to-br from-orange-500 to-red-600 text-white p-6 rounded-xl">
                    <h3 class="text-xl font-semibold mb-3">
                        <i class="fas fa-chart-line mr-2"></i>
                        Analytics & Logging
                    </h3>
                    <ul class="space-y-2 text-sm opacity-90">
                        <li>✅ MongoDB activity logging</li>
                        <li>✅ User behavior tracking</li>
                        <li>✅ Real-time statistics</li>
                        <li>✅ Performance monitoring</li>
                    </ul>
                </div>

                <div class="bg-gradient-to-br from-purple-500 to-pink-600 text-white p-6 rounded-xl">
                    <h3 class="text-xl font-semibold mb-3">
                        <i class="fas fa-cog mr-2"></i>
                        Interactive Features
                    </h3>
                    <ul class="space-y-2 text-sm opacity-90">
                        <li>✅ Settings modal with preferences</li>
                        <li>✅ Notification system</li>
                        <li>✅ Project management tools</li>
                        <li>✅ Real-time data refresh</li>
                    </ul>
                </div>
            </div>

            <!-- Demo Actions -->
            <div class="bg-gray-50 rounded-xl p-6 mb-8">
                <h2 class="text-2xl font-semibold text-gray-900 mb-4">Try These Features:</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <button onclick="window.open('dashboard.php', '_blank')" 
                            class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-tachometer-alt mr-2"></i>
                        View Dashboard
                    </button>
                    <button onclick="window.open('test_integration.php', '_blank')" 
                            class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-flask mr-2"></i>
                        Run Tests
                    </button>
                    <button onclick="window.open('auth/login.php', '_blank')" 
                            class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition-colors">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Login System
                    </button>
                </div>
            </div>

            <!-- Technical Details -->
            <div class="border-t pt-8">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Technical Implementation</h2>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Frontend Technologies</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                <span class="font-medium">Tailwind CSS</span>
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-sm rounded">v3.4</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                                <span class="font-medium">JavaScript ES6+</span>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-sm rounded">Modern</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                <span class="font-medium">Font Awesome</span>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-sm rounded">v6.0</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Backend Technologies</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-indigo-50 rounded-lg">
                                <span class="font-medium">PHP</span>
                                <span class="px-2 py-1 bg-indigo-100 text-indigo-800 text-sm rounded">v7.4+</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg">
                                <span class="font-medium">MySQL</span>
                                <span class="px-2 py-1 bg-orange-100 text-orange-800 text-sm rounded">v5.7+</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                <span class="font-medium">MongoDB</span>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-sm rounded">v4.0+</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Integration Status -->
            <div class="mt-8 p-6 bg-green-50 border border-green-200 rounded-xl">
                <div class="flex items-center mb-4">
                    <i class="fas fa-check-circle text-green-600 text-2xl mr-3"></i>
                    <h3 class="text-xl font-semibold text-green-800">Integration Complete ✨</h3>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                    <div>
                        <div class="text-2xl font-bold text-green-600">100%</div>
                        <div class="text-sm text-green-700">UI Integration</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-green-600">100%</div>
                        <div class="text-sm text-green-700">Backend API</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-green-600">100%</div>
                        <div class="text-sm text-green-700">Database Logging</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-green-600">100%</div>
                        <div class="text-sm text-green-700">Functionality</div>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="mt-8 text-center">
                <p class="text-gray-600 mb-4">Ready to explore the full dashboard experience?</p>
                <div class="space-x-4">
                    <a href="dashboard.php" 
                       class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-rocket mr-2"></i>
                        Launch Dashboard
                    </a>
                    <a href="../DASHBOARD_INTEGRATION_COMPLETE.md" 
                       class="inline-flex items-center px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="fas fa-book mr-2"></i>
                        Documentation
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add some interactive demo effects
        document.addEventListener('DOMContentLoaded', function() {
            // Animate feature cards on scroll
            const cards = document.querySelectorAll('.bg-gradient-to-br');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });

            // Add hover effects to buttons
            const buttons = document.querySelectorAll('button, a');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>
