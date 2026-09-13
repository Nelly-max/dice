document.addEventListener("DOMContentLoaded", () => {
    const alertToasts = document.querySelectorAll('.notificationAlert');
    
    if (alertToasts.length > 0) {
        const toastObserver = new MutationObserver((mutationsList) => {
            for (const mutation of mutationsList) {
                if (mutation.attributeName === 'style') {
                    const targetElement = mutation.target;
                    const activeDisplay = window.getComputedStyle(targetElement).display;
                    
                    if (activeDisplay !== 'none') {
                        try {
                            // Pull path securely from window configuration settings
                            const soundPath = window.LaravelAssets?.notificationSound || '/audio/notification.mp3';
                            const notificationSound = new Audio(soundPath);
                            notificationSound.volume = 0.6; 
                            notificationSound.play();
                        } catch (error) {
                            console.warn("Audio playback blocked by browser permissions until user interaction.", error);
                        }
                    }
                }
            }
        });

        alertToasts.forEach(toast => {
            toastObserver.observe(toast, { attributes: true });
        });
    }
});
