document.observe("dom:loaded", function() {
    $$('.overridden-hint-list-toggle').each(function(element) {
        element.observe('click', function (event) {
            this.toggleClassName('visible');

            var list = this.next('.overridden-hint-list');
            list.toggleClassName('visible');

            if(this.hasClassName('visible')) {
                this.setAttribute('title', Translator.translate('Click to close'));
            } else {
                this.setAttribute('title', Translator.translate('This setting is overridden at a more specific scope. Click for details.'));
            }

            event.preventDefault();
        });
    });
});