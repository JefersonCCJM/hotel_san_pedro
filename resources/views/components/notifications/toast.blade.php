{{-- Global Toast Notification Component --}}
<div
    x-data="toastNotification()"
    x-show="show"
    x-cloak
    @notify.window="showToast($event.detail)"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-y-2"
    x-transition:enter-end="opacity-100 transform translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform translate-y-0"
    x-transition:leave-end="opacity-0 transform translate-y-2"
    class="fixed top-4 right-4 z-[9999] max-w-md"
    style="display: none;">
    <div
        :class="{
            'bg-emerald-500': type === 'success',
            'bg-red-500': type === 'error',
            'bg-amber-500': type === 'warning',
            'bg-blue-500': type === 'info'
        }"
        class="rounded-xl shadow-xl p-4 text-white font-bold flex items-center space-x-3 min-w-[300px]">
        <i :class="{
            'fa-check-circle': type === 'success',
            'fa-exclamation-circle': type === 'error',
            'fa-exclamation-triangle': type === 'warning',
            'fa-info-circle': type === 'info'
        }" class="fas text-xl flex-shrink-0"></i>
        <span x-text="message" class="flex-1 text-sm"></span>
        <button @click="close()" class="text-white/80 hover:text-white transition-colors flex-shrink-0">
            <i class="fas fa-times text-sm"></i>
        </button>
    </div>
</div>

<script>
function toastNotification() {
    return {
        show: false,
        message: '',
        type: 'info',
        timer: null,

        showToast(data) {
            const payload = Array.isArray(data) ? data[0] : data;
            this.message = payload.message || '';
            this.type = payload.type || 'info';
            this.show = true;

            // Clear existing timer
            if (this.timer) {
                clearTimeout(this.timer);
            }

            // Auto-hide after 5 seconds
            const duration = payload.duration || 5000;
            this.timer = setTimeout(() => {
                this.close();
            }, duration);
        },

        close() {
            this.show = false;
            if (this.timer) {
                clearTimeout(this.timer);
                this.timer = null;
            }
        }
    }
}
</script>
