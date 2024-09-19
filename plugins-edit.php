<?php
/**
 * Show the list of activities logged.
 */
$allowed_levels = array(9);
require_once 'bootstrap.php';
log_in_required($allowed_levels);

$active_nav = 'plugins';

$page_title = __('Plugins Details', 'cftp_admin');
$current_url = get_form_action_with_existing_parameters(basename(__FILE__));

if (!isset($_GET['id'])) {
    exit_with_error_code(403);
}

$plugin_id = $_GET['id'];

$plugin = new \ProjectSend\Classes\Plugins($plugin_id);
$plugin_properties = $plugin->getPlugin($plugin_id);
if (!$plugin_properties) {
    exit_with_error_code(403);
}

// Beautify the JSON settings
$plugin_settings_json = !empty($plugin_properties['settings'])
    ? json_encode(json_decode($plugin_properties['settings']), JSON_PRETTY_PRINT)
    : '{}';  // Fallback to an empty JSON object if settings are null or empty


// Include layout files
include_once ADMIN_VIEWS_DIR . DS . 'header.php';

if ($_POST) {

    $arguments = [
        'settings' => $_POST['settings'],
        'enabled' => (isset($_POST["enabled"])) ? 1 : 0,
    ];
    $plugin->set($arguments);
    $plugin->edit();
    $edit_response = $plugin->edit_response;


    if ($edit_response['query'] == 1) {
        $flash->success(__('Asset edited successfully'));
    } else {
        $err = $edit_response["error"] ? $edit_response["error"] : 'There was an error saving to the database';
        $flash->error(__($err));
    }
    ps_redirect(BASE_URI . 'plugins-edit.php?id=' . $plugin_id);
}
?>

<div class="row">
    <div class="col-12 col-sm-12 col-lg-6">
        <div class="white-box">
            <div class="white-box-interior">
                <form name="email_test" method="post" enctype="multipart/form-data" class="form-horizontal">
                    <?php addCsrf(); ?>

                    <div class="form-group row">
                        <div class="col-sm-12">
                            <label for="name"><?php _e('Name:', 'cftp_admin'); ?></label>
                            <input type="text" id="name" class="form-control" value="<?php echo $plugin_properties['name']; ?> " disabled>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-12">
                            <label for="version"><?php _e('Version:', 'cftp_admin'); ?></label>
                            <input type="text" id="version" class="form-control" value="<?php echo $plugin_properties['version']; ?> " disabled>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-12">
                            <label for="description"><?php _e('Description:', 'cftp_admin'); ?></label>
                            <input type="text" id="description" class="form-control" value="<?php echo $plugin_properties['description']; ?> " disabled>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-12">
                            <label for="path"><?php _e('Path:', 'cftp_admin'); ?></label>
                            <input type="text" id="path" class="form-control" value="<?php echo $plugin_properties['path']; ?> " disabled>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-sm-12">
                            <label for="settings"><?php _e('Settings', 'cftp_admin'); ?></label>
                            <textarea id="jsonDisplay" name="settings" class="json-textarea"></textarea>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-12">
                            <label for="enabled">
                                <input type="checkbox" name="enabled" id="enabled" <?php echo (isset($plugin_properties['enabled']) && $plugin_properties['enabled'] == 1) ? 'checked="checked"' : ''; ?>> <?php _e('Enabled','cftp_admin'); ?>
                            </label>
                        </div>
                    </div>

                    <div class="after_form_buttons">
                        <button type="submit" name="submit" class="btn btn-wide btn-primary empty"><?php _e('Save', 'cftp_admin'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    // Format and display beautified JSON
    document.getElementById('jsonDisplay').value = `<?php echo $plugin_settings_json; ?>`;
</script>
<?php
include_once ADMIN_VIEWS_DIR . DS . 'footer.php';
?>
