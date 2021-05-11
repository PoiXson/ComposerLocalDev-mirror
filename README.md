# Composer Local Dev Plugin

A composer plugin to symlink dependencies to your local workspace.

Often we develop packages which are used by other packages, and we want changes made in one package to immediately affect other packages which depend on it. It's impractical to always test and commit changes to a library before we can easily test it in a larger project.

One way we can simply solve this problem is to create symlinks for vendor contents pointing to your local workspace copy. This also avoids needing to make any changes to your composer.json files. You tell the plugin which paths to use for whatever php namespaces, and the plugin will create symlinks for you automatically.

## Usage

In the top directory of your workspace, create a text file named localdev.json containing something similar to the following:

```
{
	"paths": {
		"pxn\\webresources": "WebResources/",
		"pxn\\phpUtils": "phpUtils/",
		"pxn\\phpPortal": "phpPortal/"
	}
}
```

The key/value pairs contains in `"paths"` is the namespace and the relative path to the project in your local workspace.

When using composer install or update, if this plugin is used, it will first remove any vendor symlinks and restore original content if available. Composer then does its normal work, after everything else is finished, the plugin starts its magic. It looks for the localdev.json file in your workspace. If it is available, and under certain other conditions, the plugin will then look for the local copy of dependencies and create symlinks to them.

This allows replacing things under vendor/ with symlinks, but without the risk of composer overwriting and trashing your files. Original vendor content is always restored before composer does anything. If the localdev.json file is not found, the plugin is disabled and does nothing.
