PHP Manual
==========
This plugin enables PHP.net docs within Vim.

Keybindings
-----------

The following keybindings are default:
* <kbd>SHIFT-k</kbd> open manual page within Vim. (default keybinding for !man)
* <kbd>CTRL-h</kbd> open the manual page in PHP.net (in your fav. browser)

You can overwrite the <kbd>CTRL-h</kbd> keybinding with the following setting:

`let g:php_manual_online_search_shortcut = ''`

Install options
---------------

* Copy these files to runtimepath. (~/.vim) 
* Add the following to your favorite plugin manager (Vundle):

`Plugin 'alvan/vim-php-manual'`

Screenshots
-----------
![doc.png](screenshots/20140829/doc.png)
