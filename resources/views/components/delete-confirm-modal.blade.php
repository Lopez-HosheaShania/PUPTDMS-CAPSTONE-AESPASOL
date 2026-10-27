@props([
    'id',
    'formId',
    'nameId',
    'title' => 'Delete Item',
    'subtitle' => 'This action requires confirmation',
    'message' => 'Are you sure you want to delete',
    'helper' => 'This item will be permanently removed.',
    'closeCallback' => null,
])

<div id="{{ $id }}" class="ui-modal modal-theme-danger global-delete-modal" aria-hidden="true">
    <form id="{{ $formId }}" method="POST" action="" class="ui-modal-card modal-md global-delete-card"
        role="dialog" aria-modal="true" aria-labelledby="{{ $id }}Title" data-ui-click="action-22499e15f3ee">
        @csrf
        @method('DELETE')

        <div class="modal-hd">
            <div class="modal-heading">
                <div class="modal-icon">
                    <i class="fa-solid fa-trash"></i>
                </div>

                <div class="modal-copy">
                    <h3 id="{{ $id }}Title" class="modal-title">
                        {{ $title }}
                    </h3>

                    <p class="modal-subtitle">
                        {{ $subtitle }}
                    </p>
                </div>
            </div>
        </div>

        <div class="modal-bd">
            <div class="global-delete-alert">
                <div class="global-delete-alert-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>

                <div class="global-delete-alert-content">
                    <p class="global-delete-question">
                        {{ $message }} this item?
                    </p>

                    <div class="global-delete-target">
                        <span class="global-delete-target-label">
                            Selected item
                        </span>

                        <strong id="{{ $nameId }}">
                            —
                        </strong>
                    </div>

                    <span class="global-delete-helper">
                        {{ $helper }}
                    </span>
                </div>
            </div>
        </div>

        <div class="modal-ft">
            <button type="button" class="ui-btn ui-btn-secondary"
                data-ui-click="{{ $closeCallback ?: 'close-modal' }}" data-ui-click-arg-0="{{ $id }}">
                Cancel
            </button>

            <button type="submit" class="ui-btn ui-btn-danger">
                <i class="fa-solid fa-trash"></i>
                <span>Delete</span>
            </button>
        </div>
    </form>
</div>
