{{-- Input Modal Component (for deposit, notes, etc.) --}}
<div
    x-data="inputModal()"
    x-show="isOpen"
    x-cloak
    @open-input-modal.window="open($event.detail)"
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
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4" id="modal-title" x-text="title"></h3>
                        <p class="text-sm text-gray-500 mb-4" x-text="text" x-show="text"></p>
                        <div class="space-y-4" id="input-modal-fields">
                            <template x-for="(field, index) in fields" :key="index">
                                <div>
                                    <label :for="'input-modal-' + index" class="block text-sm font-medium text-gray-700 mb-1" x-text="field.label"></label>
                                    <template x-if="field.type === 'textarea'">
                                        <textarea
                                            :id="'input-modal-' + index"
                                            x-model="field.value"
                                            :placeholder="field.placeholder || ''"
                                            class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            rows="3"></textarea>
                                    </template>
                                    <template x-if="field.type === 'select'">
                                        <select
                                            :id="'input-modal-' + index"
                                            x-model="field.value"
                                            class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                            <option value="" x-text="field.placeholder || 'Seleccionar...'"></option>
                                            <template x-for="(option, optIndex) in field.options" :key="optIndex">
                                                <option :value="option.value" x-text="option.label"></option>
                                            </template>
                                        </select>
                                    </template>
                                    <template x-if="!field.type || field.type === 'text' || field.type === 'number'">
                                        <input
                                            :type="field.type || 'text'"
                                            :id="'input-modal-' + index"
                                            x-model="field.value"
                                            :placeholder="field.placeholder || ''"
                                            :min="field.min"
                                            :step="field.step"
                                            class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </template>
                                    <p x-show="field.error" x-text="field.error" class="mt-1 text-xs text-red-600"></p>
                                </div>
                            </template>
                        </div>
                        <p x-show="error" x-text="error" class="mt-2 text-xs text-red-600"></p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button
                    type="button"
                    @click="confirm()"
                    :class="confirmButtonClass"
                    class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 text-base font-medium text-white focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    <span x-text="confirmText"></span>
                </button>
                <button
                    type="button"
                    @click="cancel()"
                    class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    <span x-text="cancelText"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function inputModal() {
    return {
        isOpen: false,
        title: '',
        text: '',
        fields: [],
        error: '',
        confirmText: 'Confirmar',
        cancelText: 'Cancelar',
        confirmButtonClass: 'bg-blue-600 hover:bg-blue-700',
        onConfirm: null,
        onCancel: null,
        validator: null,

        open(data) {
            this.isOpen = true;
            this.title = data.title || '';
            this.text = data.text || '';
            this.fields = data.fields || [];
            this.error = '';
            this.confirmText = data.confirmText || 'Confirmar';
            this.cancelText = data.cancelText || 'Cancelar';
            this.confirmButtonClass = data.confirmButtonClass || 'bg-blue-600 hover:bg-blue-700';
            this.onConfirm = data.onConfirm || null;
            this.onCancel = data.onCancel || null;
            this.validator = data.validator || null;

            // Clear field errors
            this.fields.forEach(field => {
                if (field) field.error = '';
            });
        },

        confirm() {
            // Validate
            this.error = '';
            if (this.validator && typeof this.validator === 'function') {
                const validation = this.validator(this.fields);
                if (!validation.valid) {
                    this.error = validation.message || 'Por favor corrige los errores';
                    if (validation.fieldErrors) {
                        validation.fieldErrors.forEach((fieldError, index) => {
                            if (this.fields[index]) {
                                this.fields[index].error = fieldError;
                            }
                        });
                    }
                    return;
                }
            }

            // Collect values
            const values = this.fields.map(field => ({
                name: field.name,
                value: field.value
            }));

            this.isOpen = false;
            if (typeof this.onConfirm === 'function') {
                this.onConfirm(values);
            }
        },

        cancel() {
            this.isOpen = false;
            this.error = '';
            if (typeof this.onCancel === 'function') {
                this.onCancel();
            }
        }
    }
}
</script>
