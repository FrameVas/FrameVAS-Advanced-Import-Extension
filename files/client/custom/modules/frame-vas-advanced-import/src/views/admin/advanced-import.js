/*
 * FrameVAS - Advanced Import
 * Author: FrameVAS - Artem Vasylenko
 */

define('frame-vas-advanced-import:views/admin/advanced-import', ['views/settings/record/edit'], function (Dep) {

    return class extends Dep {

        layoutName = 'advancedImport'
        saveAndContinueEditingAction = false
    };
});
