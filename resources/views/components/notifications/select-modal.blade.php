{{-- Selection Modal Component (for release room, payment methods, etc.) --}}
<div
    x-data="selectModal()"
    x-show="isOpen"
    x-cloak
    @open-select-modal.window="open($event.detail)"
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
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
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
            class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                <div class="text-center sm:text-left">
                    <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4" id="modal-title" x-text="title"></h3>
                    <p class="text-sm text-gray-600 mb-6" x-text="text" x-show="text"></p>
                    <div class="flex flex-col gap-3" id="select-modal-buttons">
                        <template x-for="(option, index) in options" :key="index">
                            <button
                                type="button"
                                @click="select(option.value)"
                                :class="option.class || 'bg-blue-500 hover:bg-blue-600'"
                                class="w-full py-3 px-6 text-white font-bold rounded-xl transition-colors duration-200">
                                <span x-text="option.label"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button
                    type="button"
                    @click="cancel()"
                    class="w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    <span x-text="cancelText"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function selectModal() {
    return {
        isOpen: false,
        title: '',
        text: '',
        options: [],
        cancelText: 'Cancelar',
        onSelect: null,
        onCancel: null,

        open(data) {
            this.isOpen = true;
            this.title = data.title || '';
            this.text = data.text || '';
            this.options = data.options || [];
            this.cancelText = data.cancelText || 'Cancelar';
            this.onSelect = data.onSelect || null;
            this.onCancel = data.onCancel || null;
        },

        select(value) {
            this.isOpen = false;
            if (typeof this.onSelect === 'function') {
                this.onSelect(value);
            }
        },

        cancel() {
            this.isOpen = false;
            if (typeof this.onCancel === 'function') {
                this.onCancel();
            }
        }
    }
}
</script>
