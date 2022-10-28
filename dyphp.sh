#!/bin/bash
# author: 大宇 
# Email: dyphp.com@gmail.com

splitLine="\n-------------------------\n"

php -v
echo -en "$splitLine"

#项目根目录创建及二次确认
rootConfirm(){
    if [ -d "$1" ];then 
        echo -en "\n-[warning]: $1 dir exists, there is a risk of data corruption!\n"
        read -p "*confirm app root(y/n):" appRootConfirm
        if [ "$appRootConfirm" != "y" ];then 
            echo "-app create stop"
            exit 0
        fi
    else
        mkdir -vp "$1"
    fi
}

#检查是否创建成功
checkApp(){
    if [ -f "$1/index.php" ]; then
        echo -en "-app create success"
    else
        echo -en "-app create fail"
        if [ ! -w $1 ]; then 
            echo -en "-$1 directory not writable"
        fi
    fi

    echo -en "$splitLine"
}


case "$1" in
    #web项目创建
    web)
        read -p "*setting web app root:" appRoot
        echo -en "-web root: $appRoot"

        rootConfirm $appRoot
        cp -r $(pwd)/template/web/* $appRoot
        checkApp $appRoot
        ;;

    #api项目创建
    api)
        read -p "*setting api app root:" appRoot
        echo -en "-api root: $appRoot"

        rootConfirm $appRoot
        cp -r $(pwd)/template/api/* $appRoot
        checkApp $appRoot
        ;;

    #console项目创建
    console)
        read -p "*setting console app root:" appRoot
        echo -en "-console root: $appRoot"

        rootConfirm $appRoot
        cp -r $(pwd)/template/console/* $appRoot
        checkApp $appRoot
        ;;

    *)
        echo -en "$splitLine"
        echo -en "Usage: ./dyphp.sh {web|api|console}"
        echo -en "$splitLine"
        exit 0
esac

