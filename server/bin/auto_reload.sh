#!/bin/bash
#通过inotify工具监听代码变更来重启微服务进程
#


#默认监听的文件
DEFAULT_FILE_PATH="$(dirname $(cd $(dirname $0); pwd))"
#默认监听的文件后缀名
DEFAULT_FILE_EXT="php|conf|properties"
#默认不监听的文件
DEFAULT_EXCLUDE="${DEFAULT_FILE_PATH}/(bin|command|log|storage)"
#默认服务重启间隔时间
DEFAULT_INTERVAL_TIME=10
#服务最后重启时间数组
declare -A RELOAD_TIME

#-o或--options选项后面接可接受的短选项，如ab:c::，表示可接受的短选项为-a -b -c，其中-a选项不接参数，-b选项后必须接参数，-c选项的参数为可选的
#-l或--long选项后面接可接受的长选项，用逗号分开，冒号的意义同短选项。
#-n选项后接选项解析错误时提示的脚本名字
ARGS=`getopt -o e:f:hi: -l exclude:,file:,help,interval_time: -n "$0" -- "$@"`
if [ $? != 0 ]; then
    exit 1
fi

usage() {
    echo "Usage:"
    echo "    $0 [-e|--exclude EXCLUDE] [-f|--file FILE_PATH] [-i|--interval_time INTERVAL_TIME]"
    echo "Description:"
    echo "    EXCLUDE: 不监听的文件，支持正则表达式，默认${DEFAULT_EXCLUDE}"
    echo "    FILE_PATH: 监听的文件，默认${DEFAULT_FILE_PATH}"
    echo "    INTERVAL_TIME: 服务重启间隔时间，默认${DEFAULT_INTERVAL_TIME}s"
    exit 1
}

init() {
    #监听的文件
    FILE_PATH="$DEFAULT_FILE_PATH"
    #不监听的文件
    EXCLUDE="$DEFAULT_EXCLUDE"
    #服务重启间隔时间
    INTERVAL_TIME="$DEFAULT_INTERVAL_TIME"

    #将规范化后的命令行参数分配至位置参数（$1,$2,...)
    eval set -- "${ARGS}"

    while true
    do
        case "$1" in
            -e|--exclude)
                EXCLUDE=$2
                shift 2
            ;;
            -f|--file)
                #移除末尾斜杠
                FILE_PATH=${2%*/}
                shift 2
            ;;
            -i|--interval_time)
                INTERVAL_TIME=$2
                shift 2
            ;;
            --)
                shift
                break
            ;;
            -h|--help|*)
                usage
            ;;
        esac
    done
}

reload() {
    local file_ext=$1
    local app_name=$2
    #获取当前时间戳
    local now=$(date -d "$(date '+%Y/%m/%d %H:%M:%S')" +%s)
    if [ ! ${RELOAD_TIME[$app_name]} ]; then
        RELOAD_TIME[$app_name]=0
    fi
    local range_time=`expr "$now" - ${RELOAD_TIME[$app_name]}`
    #判断服务重启时间间隔是否小于预设的间隔时间
    if [ "$range_time" -lt "$INTERVAL_TIME" ]; then
        return
    fi
    local master_process_pid=$(pidof "${app_name}_master_process")
    #判断是否有微服务主进程，并且非properties配置文件发生变更则重启服务
    if [[ "$master_process_pid" && "$file_ext" != 'properties' ]]; then
        echo "发送SIGUSR1信号到主进程：${app_name}_master_process，重启所有worker进程"
        kill -USR1 "$master_process_pid"
    else
        php "$(dirname ${FILE_PATH})/jyb_microservice_framework/bin/jmfServer.php" "$app_name" restart
    fi
    RELOAD_TIME[$app_name]="$now"
}

batch_reload() {
    local file_ext=$1
    for app_name in `ls "$FILE_PATH"`; do
        #有配置文件才重启微服务
        local app_config_file="${FILE_PATH}/${app_name}/conf/provider.properties"
        if [ -f "$app_config_file" ]; then
            reload "$file_ext" "$app_name"
        fi
    done
}

main() {
    init
    #监听文件的创建、删除、修改事件
    inotifywait -mr --timefmt '%Y/%m/%d %H:%M:%S' --format '%T %w %f' -e create,delete,modify --exclude "$EXCLUDE" "$FILE_PATH" | while read date t d f; do
        echo "$date $t $d$f"
        #文件后缀名为php|conf|properties才重启微服务
        local file_ext=${f##*.}
        if ! [[ "|${DEFAULT_FILE_EXT}|" =~ "|${file_ext}|" ]]; then
            continue
        fi
        local len=`expr ${#FILE_PATH} + 1`
        local app_path=${d:${len}}
        local app_name=${app_path%%/*}

        #判断是否有微服务配置文件，没有则重启所有的微服务
        local app_config_file="${FILE_PATH}/${app_name}/conf/provider.properties"
        if [ -f "$app_config_file" ]; then
            reload "$file_ext" "$app_name"
        else
            batch_reload "$file_ext"
        fi
    done
}

main
