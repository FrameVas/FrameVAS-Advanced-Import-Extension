/*
 * FrameVAS - Advanced Import
 * Author: FrameVAS - Artem Vasylenko
 */

define('frame-vas-advanced-import:views/import/index', ['views/import/index'], function (Dep) {

    /**
     * The core import wizard with the field-mapping step replaced.
     */
    return class extends Dep {

        createView(key, viewName, ...rest) {
            if (key === 'step' && viewName === 'views/import/step2') {
                viewName = 'frame-vas-advanced-import:views/import/step2';
            }

            return super.createView(key, viewName, ...rest);
        }
    };
});
