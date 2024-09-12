# Plugins functionality
Plugins allow the PS to integrate different logic that could have a global impact. 

Each plugin needs to be installed in the `Plugins` Folder. There needs to be a folder structure:
- index.php -> custom code on how your plugin operats
- install.php -> required code to install the plugin into PS
- config.php -> required code on how to configure the plugin


Plugins will have a page in the admin portal where each plugin can be enabled and customized. 