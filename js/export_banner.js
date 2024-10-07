// Requires jQuery (can use both $ or jQuery)
require(['jquery'], function (jQuery) {
    window.grade_export_banner_onload = function (id) {
        jQuery("<input>").attr({
            type: "hidden",
            id: "btype",
            name: "btype"
        }).appendTo("#page-content .mform").first();

        // Deselect everything.
        jQuery("input:checkbox").prop("checked", false);

        // Select desired settings.
        // Only Letter output.
        jQuery("input:checkbox#id_display_letter").prop("checked", true);

        // Only Active users.
        jQuery("input:checkbox#id_export_onlyactive").prop("checked", true);

        // Only Course total.
        let items = ":checkbox[name=\'itemids[id]\']";
        jQuery("#id_gradeitemscontainer input" + items).prop("checked", true);

        // Enable submit button.
        jQuery(".bannerbutton").show();
    }
});