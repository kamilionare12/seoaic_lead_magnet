const $ = jQuery;

class AjaxHandler {
    constructor() {
      this.subscribeToAjaxEvents();
    }
  
    subscribeToAjaxEvents() {
        $(document).ajaxSuccess((event, xhr, settings) => {
            if (settings.data && settings.data.startsWith('action=seoaic_')) {
                this.onSuccess(xhr.responseJSON);
            }
        });

        // $(document).ajaxError((event, xhr, settings, error) => {
        //     if (settings.data && settings.data.startsWith('action=seoaic_')) {
        //         this.showModal('An unexpected error occurred');
        //     }
        // });
  
        $(document).ajaxComplete((event, xhr, settings) => {
            if (settings.data && settings.data.startsWith('action=seoaic_')) {
                this.onComplete();
            }
        });
    }
  
    onSuccess(data) {
        if (data === undefined) {
            return;
        }

        if (data.status === 'alert') {
            this.showModal(data);
        }

        if (data.status === 'reload') {
            this.reloadPage();
        }

        if (data.status === 'redirect') {
            this.redirectTo(data.redirectTo);
        }
    }
  
    onComplete() {
        $('#seoaic-admin-body').removeClass('seoaic-loading')
    }

    showModal(data) {
        let modal = $('#seoaic-alert-modal');

        if (false !== data.message) {
            modal.find('.seoaic-popup__content .modal-content').html(data.message);
        }

        if (data.button) {
            modal.find('.seoaic-popup__footer .mr-15').remove();
            modal.find('.seoaic-popup__btn').before(data.button);
        }

        $('#seoaic-admin-body').addClass('seoaic-blur');
        $('body').addClass('modal-show');
        modal.fadeIn(200);
    }

    reloadPage() {
        window.location.reload();
    }

    redirectTo(url) {
        window.location.href = url;
    }
}
  
const ajaxHandler = new AjaxHandler();
  