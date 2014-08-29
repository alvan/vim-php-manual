" == "acomment" == {{{
"
"          File:  php.vim
"        Author:  Alvan
"      Modifier:  Alvan
"      Modified:  2014-07-22
"   Description:  PHP Manual for Vim
"
" --}}}
" Only do this when not done yet for this buffer
if exists("b:did_ftplugin_phpmanual")
    finish
endif
let b:did_ftplugin_phpmanual = 1

exec 'setlocal rtp+='.fnameescape(expand('<sfile>:h:h')).'/manual'
setlocal keywordprg=:help

vnoremap <silent> <buffer> <C-A-K> y:call phpmanual#online#open(@@)<CR>
nnoremap <silent> <buffer> <C-A-K> :call phpmanual#online#open()<CR>
