export const customerAuth = JSON.parse(
    document.querySelector('meta[name="customer-auth"]')?.content ||
    '{"authenticated":false,"id":null}'
);

export const isAuthenticated = customerAuth.authenticated;
export const customerId = customerAuth.id;


if (isAuthenticated) {
    const localCart = JSON.parse(localStorage.getItem('cart') || '[]');

    if (localCart.length) {
        fetch('/cart/sync', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ items: localCart })
        }).then(() => {
            localStorage.removeItem('cart');
        });
    }
}