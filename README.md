## ================================================================================
##  Composer Local Dev Plugin
## Copyright (c) 2019-2025 Mattsoft/PoiXson
## <https://mattsoft.net> <https://poixson.com>
## Released under the AGPL 3.0 + ADD-PXN-V1
##
## Description: A composer plugin to symlink dependencies to your local workspace.
##
## ================================================================================
##
## This program is free software: you can redistribute it and/or modify it under
## the terms of the GNU Affero General Public License + ADD-PXN-V1 as published by
## the Free Software Foundation, either version 3 of the License, or (at your
## option) any later version, with the addition of ADD-PXN-V1.
##
## This program is distributed in the hope that it will be useful, but WITHOUT ANY
## WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
## PARTICULAR PURPOSE.
##
## See the GNU Affero General Public License for more details
## <http://www.gnu.org/licenses/agpl-3.0.en.html> and Addendum ADD-PXN-V1
## <https://dl.poixson.com/ADD-PXN-V1.txt>
##
## **ADD-PXN-V1 - Addendum to the GNU Affero General Public License (AGPL)**
## This Addendum is an integral part of the GNU Affero General Public License
## (AGPL) under which this software is licensed. By using, modifying, or
## distributing this software, you agree to the following additional terms:
##
## 1. **Source Code Availability:** Any distribution of the software, including
##    modified versions, must include the complete corresponding source code. This
##    includes all modifications made to the original source code.
## 2. **Free of Charge and Accessible:** The source code and any modifications to
##    the source code must be made available to all with reasonable access to the
##    internet, free of charge. No fees may be charged for access to the source
##    code or for the distribution of the software, whether in its original or
##    modified form. The source code must be accessible in a manner that allows
##    users to easily obtain, view, and modify it. This can be achieved by
##    providing a link to a publicly accessible repository (e.g., GitHub, GitLab)
##    or by including the source code directly with the distributed software.
## 3. **Documentation of Changes:** When distributing modified versions of the
##    software, you must provide clear documentation of the changes made to the
##    original source code. This documentation should be included with the source
##    code, and should be easily accessible to users.
## 4. **No Additional Restrictions:** You may not impose any additional
##    restrictions on the rights granted by the AGPL or this Addendum. All
##    recipients of the software must have the same rights to use, modify, and
##    distribute the software as granted under the AGPL and this Addendum.
## 5. **Acceptance of Terms:** By using, modifying, or distributing this software,
##    you acknowledge that you have read, understood, and agree to comply with the
##    terms of the AGPL and this Addendum.
## ================================================================================


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
