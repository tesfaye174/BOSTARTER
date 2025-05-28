import React, { useState, useEffect } from 'react';
import { BellIcon } from '@heroicons/react/24/outline';
import { motion, AnimatePresence } from 'framer-motion';
import WebSocketService from '../services/WebSocketService';

const NotificationCenter = () => {
    const [notifications, setNotifications] = useState([]);
    const [unreadCount, setUnreadCount] = useState(0);
    const [isOpen, setIsOpen] = useState(false);
    const [isWebSocketConnected, setIsWebSocketConnected] = useState(false);

    const fetchNotifications = async () => {
        try {
            const response = await fetch('/api/notifications.php?action=unread');
            const data = await response.json();
            if (data.status === 'success') {
                setNotifications(data.data);
            }
        } catch (error) {
            console.error('Errore nel recupero delle notifiche:', error);
        }
    };

    const fetchUnreadCount = async () => {
        try {
            const response = await fetch('/api/notifications.php?action=count');
            const data = await response.json();
            if (data.status === 'success') {
                setUnreadCount(data.count);
            }
        } catch (error) {
            console.error('Errore nel recupero del conteggio:', error);
        }
    };

    useEffect(() => {
        // Recupera l'ID utente e il token dalla sessione
        const userId = localStorage.getItem('user_id');
        const token = localStorage.getItem('token');

        if (userId && token) {
            // Configura il callback per l'autenticazione
            WebSocketService.setAuthCallback((success) => {
                setIsWebSocketConnected(success);
                if (success) {
                    // Avvia il ping periodico
                    const pingInterval = setInterval(() => {
                        WebSocketService.ping();
                    }, 30000);

                    return () => clearInterval(pingInterval);
                }
            });

            // Connetti al WebSocket
            WebSocketService.connect(userId, token);

            // Aggiungi listener per le notifiche
            WebSocketService.addListener('notification', (notification) => {
                setNotifications(prev => [notification, ...prev]);
                setUnreadCount(prev => prev + 1);
            });
        }

        // Carica le notifiche iniziali
        fetchNotifications();
        fetchUnreadCount();

        // Aggiorna ogni 30 secondi
        const interval = setInterval(() => {
            fetchNotifications();
            fetchUnreadCount();
        }, 30000);

        return () => {
            clearInterval(interval);
            WebSocketService.disconnect();
        };
    }, []);

    const markAsRead = async (notificationId) => {
        try {
            const response = await fetch('/api/notifications.php?action=mark_read', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ notification_id: notificationId }),
            });
            const data = await response.json();
            if (data.status === 'success') {
                setNotifications(prev =>
                    prev.map(notification =>
                        notification.id === notificationId
                            ? { ...notification, is_read: true }
                            : notification
                    )
                );
                setUnreadCount(prev => Math.max(0, prev - 1));
            }
        } catch (error) {
            console.error('Errore nel marcare la notifica come letta:', error);
        }
    };

    const markAllAsRead = async () => {
        try {
            const response = await fetch('/api/notifications.php?action=mark_all_read', {
                method: 'PUT',
            });
            const data = await response.json();
            if (data.status === 'success') {
                setNotifications(prev =>
                    prev.map(notification => ({ ...notification, is_read: true }))
                );
                setUnreadCount(0);
            }
        } catch (error) {
            console.error('Errore nel marcare tutte le notifiche come lette:', error);
        }
    };

    const deleteNotification = async (notificationId) => {
        try {
            const response = await fetch(`/api/notifications.php?id=${notificationId}`, {
                method: 'DELETE',
            });
            const data = await response.json();
            if (data.status === 'success') {
                setNotifications(prev =>
                    prev.filter(notification => notification.id !== notificationId)
                );
                setUnreadCount(prev =>
                    Math.max(0, prev - 1)
                );
            }
        } catch (error) {
            console.error('Errore nell\'eliminazione della notifica:', error);
        }
    };

    return (
        <div className="relative">
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="relative p-2 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
            >
                <BellIcon className="h-6 w-6" />
                {unreadCount > 0 && (
                    <span className="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full">
                        {unreadCount}
                    </span>
                )}
                {!isWebSocketConnected && (
                    <span className="absolute bottom-0 right-0 w-2 h-2 bg-yellow-500 rounded-full transform translate-x-1/2 translate-y-1/2" />
                )}
            </button>

            <AnimatePresence>
                {isOpen && (
                    <motion.div
                        initial={{ opacity: 0, y: 10 }}
                        animate={{ opacity: 1, y: 0 }}
                        exit={{ opacity: 0, y: 10 }}
                        className="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden z-50"
                    >
                        <div className="p-4 border-b border-gray-200 dark:border-gray-700">
                            <div className="flex justify-between items-center">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                    Notifiche
                                </h3>
                                {notifications.length > 0 && (
                                    <button
                                        onClick={markAllAsRead}
                                        className="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                    >
                                        Segna tutte come lette
                                    </button>
                                )}
                            </div>
                        </div>

                        <div className="max-h-96 overflow-y-auto">
                            {notifications.length === 0 ? (
                                <div className="p-4 text-center text-gray-500 dark:text-gray-400">
                                    Nessuna notifica
                                </div>
                            ) : (
                                notifications.map((notification) => (
                                    <div
                                        key={notification.id}
                                        className={`p-4 border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 ${
                                            !notification.is_read ? 'bg-blue-50 dark:bg-blue-900/20' : ''
                                        }`}
                                    >
                                        <div className="flex justify-between items-start">
                                            <div className="flex-1">
                                                <p className="text-sm text-gray-900 dark:text-white">
                                                    {notification.message}
                                                </p>
                                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    {new Date(notification.created_at).toLocaleString()}
                                                </p>
                                            </div>
                                            <div className="flex space-x-2">
                                                {!notification.is_read && (
                                                    <button
                                                        onClick={() => markAsRead(notification.id)}
                                                        className="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                                    >
                                                        Segna come letta
                                                    </button>
                                                )}
                                                <button
                                                    onClick={() => deleteNotification(notification.id)}
                                                    className="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                                >
                                                    Elimina
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
};

export default NotificationCenter; 