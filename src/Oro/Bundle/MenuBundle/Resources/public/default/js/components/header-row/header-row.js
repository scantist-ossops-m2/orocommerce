define(function(require) {
    'use strict';

    var HeaderRowComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var $ = require('jquery');

    HeaderRowComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            isMobile: false
        },

        /**
         *
         * @param options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            if (this.options.isMobile) {
                var windowHeight = $(window).height();
                var menuHeight =  windowHeight - this.options._sourceElement.height();
                var dropdowns = this.options._sourceElement.find('.header-row__dropdown-mobile');

                $.each(dropdowns, function(index, dropdown) {
                    var dropdownHeight = $(dropdown).height();

                    if (dropdownHeight >= windowHeight) {
                        $(dropdown)
                            .addClass('header-row__dropdown-mobile--scroll');

                        $(dropdown)
                            .parent()
                            .css('height', menuHeight);
                    }
                });
            }
        }
    });

    return HeaderRowComponent;
});
