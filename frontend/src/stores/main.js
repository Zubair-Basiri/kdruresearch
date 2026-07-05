// src/stores/main.js
import { defineStore } from 'pinia';

export const useMainStore = defineStore('main', {
    state: () => ({
        shareOffcanvas: false,
        carts: [], // Add this if needed
    }),
    getters: {
        isShareOffcanvasOpen: (state) => state.shareOffcanvas,
        cartItems: (state) => state.carts,
    },
    actions: {
        openBottomCanvas(payload) {
            this.shareOffcanvas = payload.value;
        },
        closeShareOffcanvas() {
            this.shareOffcanvas = false;
        },
        addToCart(item) {
            this.carts.push(item);
        },
        removeFromCart(index) {
            this.carts.splice(index, 1);
        },
    },
});