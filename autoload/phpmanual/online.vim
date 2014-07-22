" == "acomment" == {{{
"
"          File:  online.vim
"          Path:  ~/.vim/autoload/phpmanual/
"        Author:  Alvan
"      Modifier:  Alvan
"      Modified:  2014-07-22
"   Description:  PHP online manual for Vim
"
" --}}}

func! phpmanual#online#open(...)
    let url = ''

    if a:0 < 1
        let mcs = '[a-zA-Z0-9_]'
        let str = getline(".")
        let col = col(".")
        let end = col("$")

        let num = col - 1
        while num >= 0
            if strpart(str, num, 1) !~ mcs
                break
            endif
            let l = num
            let num -= 1
        endw

        if !exists("l")
            echo 'manual.ERR: The current content under the cursor is not a keyword.'
            return
        endif

        let num = col - 1
        while num <= end
            if strpart(str, num, 1) !~ mcs
                break
            endif
            let r = num
            let num += 1
        endw

        let key = strpart(str, l, r-l+1)

        if key != ''
            if key =~ '^[A-Z]'
                let url = 'http://php.net/manual/class.'.tolower(key).'.php'
            else
                let url = 'http://php.net/manual/function.'.substitute(tolower(key), '_', '-', 'g').'.php'
            endif
        endif
    el
        if a:1 !~ '^\s*$'
            let url = 'http://php.net/results.php?q='.a:1.'&p=all'
        endif
    en

    if url == ''
        echo 'manual.ERR: no online manual URL.'
        return
    en

    call system('xdg-open '.shellescape(url).' &')
endf

" vim:ft=vim:ff=unix:tabstop=4:shiftwidth=4:softtabstop=4:expandtab
" End of file : online.vim
