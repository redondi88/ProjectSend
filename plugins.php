<?php
/**
 * Show the list of activities logged.
 */
$allowed_levels = array(9);
require_once 'bootstrap.php';
log_in_required($allowed_levels);

$active_nav = 'plugins';

$page_title = __('Plugins', 'cftp_admin');
$current_url = get_form_action_with_existing_parameters(basename(__FILE__));

// Query the clients
$params = [];

// Header buttons
$header_action_buttons = [
    [
        'url' => 'plugin_install.php',
        'label' => __('Install Plugin', 'cftp_admin'),
    ],
];

// Include layout files
include_once ADMIN_VIEWS_DIR . DS . 'header.php';
$cq = "SELECT * FROM " . TABLE_PLUGINS;
// Pre-query to count the total results
$sql = $dbh->prepare($cq);

$sql->execute($params);
$count = $sql->rowCount();

// Flash errors
if (!$count) {
    if (isset($no_results_error)) {
        switch ($no_results_error) {
            case 'search':
                $flash->error(__('Your search keywords returned no results.', 'cftp_admin'));
                break;
            case 'filter':
                $flash->error(__('The filters you selected returned no results.', 'cftp_admin'));
            break;
        }
    } else {
        $flash->warning(__('There are no plugins yet.', 'cftp_admin'));
    }
}
include_once LAYOUT_DIR . DS . 'search-filters-bar.php';

?>
<form action="<?php echo $current_url; ?>" name="clients_list" method="post" class="form-inline batch_actions">
    <?php addCsrf(); ?>

    <div class="row">
        <div class="col-12">
            <?php
            if ($count > 0) {
                // Generate the table using the class.
                $table = new \ProjectSend\Classes\Layout\Table([
                    'id' => 'clients_tbl',
                    'class' => 'footable table',
                    'origin' => basename(__FILE__),
                ]);

                $thead_columns = array(
                    array(
                        'sortable' => true,
                        'sort_url' => 'id',
                        'content' => __('ID', 'cftp_admin'),
                    ),
                    array(
                        'sortable' => true,
                        'sort_url' => 'name',
                        'content' => __('Name', 'cftp_admin'),
                    ),
                    array(
                        'sortable' => true,
                        'sort_url' => 'path',
                        'content' => __('Path', 'cftp_admin'),
                        'hide' => 'phone,tablet',
                    ),
                    array(
                        'content' => __('enabled', 'cftp_admin'),

                    ),
                    array(
                        'content' => __('Edit', 'cftp_admin'),

                    )
                );
                $table->thead($thead_columns);
                
                $sql->setFetchMode(PDO::FETCH_ASSOC);
                while ($row = $sql->fetch()) {
                    $table->addRow();
                    // Add the cells to the row
                    $enabled = '<div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckCheckedDisabled" '
                    . ($row["enabled"] == 1 ? 'checked' : '') . ' disabled>
                  </div>';
                  
                    $tbody_cells = array(

                        array(
                            'content' => $row["id"],
                        ),
                        array(
                            'content' => $row["name"],
                        ),
                        array(
                            'content' => $row["path"],
                        ),
                        array(
                            'content' =>  $enabled,
                        ),
                        array(
                            'actions' => true,
                            'content' =>  '<a href="plugins-edit.php?id=' . $row["id"] . '" class="btn btn-primary btn-sm"><i class="fa fa-pencil"></i><span class="button_label">' . __('Edit', 'cftp_admin') . '</span></a>' . "\n"
                        ),
                    );

                    foreach ($tbody_cells as $cell) {
                        $table->addCell($cell);
                    }

                    $table->end_row();
                }

                echo $table->render();
            }
        ?>
        </div>
    </div>
</form>
   
    
<?php
include_once ADMIN_VIEWS_DIR . DS . 'footer.php';
?>