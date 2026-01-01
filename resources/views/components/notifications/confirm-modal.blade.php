{{-- Confirmation Modal Component --}}
<div
    x-data="confirmModal()"
    x-show="isOpen"
    x-cloak
    @open-confirm-modal.window="open($event.detail)"
    class="fixed inset-0 z-[9998] overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
    style="display: none;">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        {{-- Backdrop --}}
        <div
            x-show="isOpen"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="cancel()"
            class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm"
            aria-hidden="true"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        {{-- Modal --}}
        <div
            x-show="isOpen"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
            <div class="bg-white px-6 pt-6 pb-4 sm:px-8 sm:pt-8 sm:pb-6">
                <div class="sm:flex sm:items-start">
                    <div :class="{
                        'bg-emerald-100': iconType === 'success',
                        'bg-red-100': iconType === 'error' || isDestructive,
                        'bg-amber-100': iconType === 'warning',
                        'bg-blue-100': iconType === 'info',
                        'bg-gray-100': !iconType && !isDestructive
                    }" class="mx-auto flex-shrink-0 flex items-center justify-center h-14 w-14 rounded-full sm:mx-0 sm:h-12 sm:w-12">
                        <i :class="{
                            'fa-check-circle text-emerald-600': iconType === 'success',
                            'fa-exclamation-circle text-red-600': iconType === 'error' || isDestructive,
                            'fa-exclamation-triangle text-amber-600': iconType === 'warning',
                            'fa-info-circle text-blue-600': iconType === 'info',
                            'fa-trash text-red-600': isDestructive && !iconType,
                            'fa-question-circle text-gray-600': !iconType && !isDestructive
                        }" class="fas text-2xl"></i>
                    </div>
                    <div class="mt-4 text-center sm:mt-0 sm:ml-5 sm:text-left flex-1">
                        <h3 class="text-xl leading-6 font-bold text-gray-900 mb-2" id="modal-title" x-text="title"></h3>
                        <div class="mt-2" x-html="html" x-show="html"></div>
                        <p class="text-sm text-gray-600 leading-relaxed" x-text="text" x-show="text && !html"></p>
                        <p class="text-xs text-gray-500 mt-3 italic" x-text="warningText" x-show="warningText"></p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 sm:px-8 sm:py-5 sm:flex sm:flex-row-reverse gap-3">
                <button
                    type="button"
                    @click="confirm()"
                    :class="confirmButtonClass"
                    class="w-full inline-flex items-center justify-center rounded-xl border border-transparent shadow-sm px-6 py-3 text-sm font-bold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all sm:ml-3 sm:w-auto">
                    <i :class="confirmIcon" class="fas mr-2" x-show="confirmIcon"></i>
                    <span x-text="confirmText"></span>
                </button>
                <button
                    type="button"
                    @click="cancel()"
                    class="mt-3 w-full inline-flex items-center justify-center rounded-xl border border-gray-300 shadow-sm px-6 py-3 bg-white text-sm font-bold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all sm:mt-0 sm:ml-3 sm:w-auto">
                    <span x-text="cancelText"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmModal() {
    return {
        isOpen: false,
        title: '',
        text: '',
        html: '',
        iconType: '',
        warningText: '',
        confirmText: 'Confirmar',
        cancelText: 'Cancelar',
        confirmButtonClass: 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500',
        confirmIcon: '',
        isDestructive: false,
        onConfirm: null,
        onCancel: null,

        open(data) {
            this.isOpen = true;
            this.title = data.title || 'Confirmar acción';
            this.text = data.text || '';
            this.html = data.html || '';
            this.iconType = data.icon || '';
            this.warningText = data.warningText || '';
            this.confirmText = data.confirmText || 'Confirmar';
            this.cancelText = data.cancelText || 'Cancelar';
            this.confirmIcon = data.confirmIcon || '';
            this.isDestructive = data.isDestructive || false;
            
            // Set default button class based on destructive flag
            if (this.isDestructive) {
                this.confirmButtonClass = data.confirmButtonClass || 'bg-red-600 hover:bg-red-700 focus:ring-red-500';
                if (!this.confirmIcon) {
                    this.confirmIcon = 'fa-trash';
                }
            } else {
                this.confirmButtonClass = data.confirmButtonClass || 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500';
            }
            
            this.onConfirm = data.onConfirm || null;
            this.onCancel = data.onCancel || null;
        },

        confirm() {
            this.isOpen = false;
            if (typeof this.onConfirm === 'function') {
                this.onConfirm();
            }
            this.reset();
        },

        cancel() {
            this.isOpen = false;
            if (typeof this.onCancel === 'function') {
                this.onCancel();
            }
            this.reset();
        },

        reset() {
            this.title = '';
            this.text = '';
            this.html = '';
            this.iconType = '';
            this.warningText = '';
            this.confirmText = 'Confirmar';
            this.cancelText = 'Cancelar';
            this.confirmButtonClass = 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500';
            this.confirmIcon = '';
            this.isDestructive = false;
            this.onConfirm = null;
            this.onCancel = null;
        }
    }
}
</script>
