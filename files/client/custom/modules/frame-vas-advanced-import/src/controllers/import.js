/*
 * FrameVAS - Advanced Import
 * Author: FrameVAS - Artem Vasylenko
 */

define('frame-vas-advanced-import:controllers/import', ['controllers/import'], function (Dep) {

    /**
     * The core Import controller that opens the advanced import wizard.
     */
    return class extends Dep {

        main(view, options, callback, params) {
            if (view === 'views/import/index') {
                view = 'frame-vas-advanced-import:views/import/index';
            }

            return super.main(view, options, callback, params);
        }
    };
});
