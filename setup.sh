#! /bin/sh

# shellcheck disable=SC2317,SC2004,SC2034
# (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
#
# All Rights Reserved. See copyright.txt for details and a complete list of authors.
# Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

# This file sets permissions and creates relevant folders for Tiki.
#

# part 0 - choose production mode or verbose debugging mode
# ---------------------------------------------------------

# shellcheck disable=SC1091
. ./path_constants.sh
. ./php_version_constants.sh

DEBUG=0 # production mode
#DEBUG=1 # debugging mode
DEBUG_PATH=0 # production mode
#DEBUG_PATH=1 # debugging mode
DEBUG_UNIX=0 # production mode
#DEBUG_UNIX=1 # debugging mode
DEBUG_PREFIX='D>'
ECHOFLAG=1 # one empty line before printing used options in debugging mode
# not used PATCHCOMPOSERFLAG="0" # patch composer.phar to avoid the warnings
                      # unfortunately, this file checks its own signature
                      # and thus does not allow modifications
# log composer instead of screen out# log composer instead of screen outputput
LOGCOMPOSERFLAG="0" # default for composer output 
TIKI_COMPOSER_INSTALL_LOG=tiki-composer-install.log
TIKI_COMPOSER_SELF_UPDATE_LOG=tiki-composer-self-update.log

# part 1 - preliminaries
# ----------------------

PERMISSIONCHECK_DIR="permissioncheck"
SEARCHPATH="/bin /usr/bin /sbin /usr/sbin /usr/local/bin /usr/local/sbin /opt/bin /opt/sbin /opt/local/bin /opt/local/sbin"
#USE_CASES_FILE="usecases.txt"
USE_CASES_FILE="usecases.bin"
USE_CASES_PATH=${PERMISSIONCHECK_DIR}
USE_CASES_NAME=${USE_CASES_PATH}/${USE_CASES_FILE}
WHAT_NEXT_AFTER_c='f'
WHAT_NEXT_AFTER_f='x'

# Composer: If you are installing via a released Tiki package (zip, tar.gz,
# tar.bz2, 7z), you can and should skip using Composer. If you are installing and
# upgrading via GIT, you need to run Composer after 'git clone' and 'git pull'.
# More info at https://doc.tiki.org/Composer

if [ -d ".svn" ] || [ -d ".git" ]; then
    DEFAULT_WHAT='b'
else
    DEFAULT_WHAT='f'
fi

define_path() {
# define PATH for executable mode
if [ ${DEBUG_PATH} = '1' ] ; then
    echo ${DEBUG_PREFIX}
    echo ${DEBUG_PREFIX} old path: "${PATH}"
    echo ${DEBUG_PREFIX}
fi
#PATH="${PATH}:/bin:/usr/bin:/sbin:/usr/sbin:/usr/local/bin:/usr/local/sbin:/opt/bin:/opt/sbin:/opt/local/bin:/opt/local/sbin"
#for ADDPATH in `echo /bin /usr/bin /sbin /usr/sbin /usr/local/bin /usr/local/sbin /opt/bin /opt/sbin /opt/local/bin /opt/local/sbin` ; do
for ADDPATH in ${SEARCHPATH} ; do
    if [ -d "${ADDPATH}" ] ; then
        PATH="${PATH}:${ADDPATH}"
        if [ ${DEBUG_PATH} = '1' ] ; then
             echo ${DEBUG_PREFIX} "${ADDPATH}" exists
        fi
    else
        if [ ${DEBUG_PATH} = '1' ] ; then
            echo ${DEBUG_PREFIX} "${ADDPATH}" does not exist
        fi
    fi
done
# Virtualmin per-domain php versions
PREPENDPATH="$(dirname "$0")/bin $(dirname "$0")/../bin"
for PREPPATH in ${PREPENDPATH} ; do
    if [ -d "${PREPPATH}" ] ; then
        PATH="${PREPPATH}:${PATH}"
    fi
done
if [ ${DEBUG_PATH} = '1' ] ; then
    echo ${DEBUG_PREFIX}
    echo ${DEBUG_PREFIX} new path: "${PATH}"
fi
}

define_path

# set used commands
if [ ${DEBUG_UNIX} = '1' ] ; then
    echo ${DEBUG_PREFIX}
    echo ${DEBUG_PREFIX} before:
    echo ${DEBUG_PREFIX} CAT="${CAT}"
    echo ${DEBUG_PREFIX} CHGRP="${CHGRP}"
    echo ${DEBUG_PREFIX} CHMOD="${CHMOD}"
    echo ${DEBUG_PREFIX} CHOWN="${CHOWN}"
    echo ${DEBUG_PREFIX} FIND="${FIND}"
    echo ${DEBUG_PREFIX} ID="${ID}"
    echo ${DEBUG_PREFIX} MKDIR="${MKDIR}"
    echo ${DEBUG_PREFIX} MV="${MV}"
    echo ${DEBUG_PREFIX} RM="${RM}"
    echo ${DEBUG_PREFIX} SORT="${SORT}"
    echo ${DEBUG_PREFIX} TOUCH="${TOUCH}"
    echo ${DEBUG_PREFIX} UNIQ="${UNIQ}"
    echo ${DEBUG_PREFIX} TR="${TR}"
fi
# list of commands
CAT=$(which cat)
CHGRP=$(which chgrp)
CHMOD=$(which chmod)
CHOWN=$(which chown)
CUT=$(which cut)
FIND=$(which find)
GREP=$(which grep)
ID=$(which id)
MKDIR=$(which mkdir)
MV=$(which mv)
PHPCLI=$(which php || command -v php)
RM=$(which rm)
SORT=$(which sort)
TOUCH=$(which touch)
UNIQ=$(which uniq)
TR=$(which tr)
if [ ${DEBUG_UNIX} = '1' ] ; then
    echo ${DEBUG_PREFIX}
    echo ${DEBUG_PREFIX} after:
    echo ${DEBUG_PREFIX} CAT="${CAT}"
    echo ${DEBUG_PREFIX} CHGRP="${CHGRP}"
    echo ${DEBUG_PREFIX} CHMOD="${CHMOD}"
    echo ${DEBUG_PREFIX} CHOWN="${CHOWN}"
    echo ${DEBUG_PREFIX} FIND="${FIND}"
    echo ${DEBUG_PREFIX} ID="${ID}"
    echo ${DEBUG_PREFIX} MKDIR="${MKDIR}"
    echo ${DEBUG_PREFIX} MV="${MV}"
    echo ${DEBUG_PREFIX} RM="${RM}"
    echo ${DEBUG_PREFIX} SORT="${SORT}"
    echo ${DEBUG_PREFIX} TOUCH="${TOUCH}"
    echo ${DEBUG_PREFIX} UNIQ="${UNIQ}"
    echo ${DEBUG_PREFIX} TR="${TR}"
fi

# hint for users
#POSSIBLE_COMMANDS='open|fix|nothing'
POSSIBLE_COMMANDS="composer|fix|insane|mixed|morepain|moreworry|nothing|open|pain|paranoia|paranoia-suphp|risky|sbox|sboxworkaround|suphpworkaround|worry"
#HINT_FOR_USER="Type 'fix', 'nothing' or 'open' as command argument."
# unused HINT_FOR_USER="\nType 'fix', 'nothing' or 'open' as command argument.
# \nIf you used Tiki Permission Check via PHP, you know which of the following commands will probably work:
# \ninsane mixed morepain moreworry pain paranoia paranoia-suphp risky sbox worry
# \nMore documentation: https://doc.tiki.org/Permission+Check\n"

hint_for_users() {
    ${CAT} <<EOF
Type 'fix', 'nothing' or 'open' as command argument.
If you used Tiki Permission Check via PHP, you know which of the following commands will probably work:
insane mixed morepain moreworry pain paranoia paranoia-suphp workaround risky sbox worry

There are some other commands recommended for advanced users only.
More documentation about this: https://doc.tiki.org/Permission+Check
EOF
}

usage() {
#usage: $0 [<switches>] open|fix
    #cat <<EOF
    ${CAT} <<EOF
usage: sh $(basename "$0") [<switches>] ${POSSIBLE_COMMANDS}
or if executable
usage: $0 [<switches>] ${POSSIBLE_COMMANDS}
-h           show help
-u user      owner of files (default: $AUSER)
-g group     group of files (default: $AGROUP)
-v virtuals  list of virtuals (for multitiki, example: "www1 www2")
-p php       alternate PHP command (default: php)
-n           not prompt for user and group, assume current
-k           don't guess user and group from context, keep same user and group as web root
-d off|on    disable|enable debugging mode (override script default)
-q           quiet (workaround to silence composer, e.g. in cron scripts)

There are some other commands recommended for advanced users only.
More documentation about this: https://doc.tiki.org/Permission+Check

Example: sh $(basename "$0") -n fix
EOF
}

# evaluate command line options (cannot be done inside a function)
set_debug() {
    case ${OPTARG} in
        off) DEBUG=0 ;;
        on) DEBUG=1 ;;
        *) DUMMY="no override, default remains active" ;;
    esac
}

OPT_AUSER=
OPT_AGROUP=
OPT_VIRTUALS=
OPT_PHPCLI=
OPT_USE_CURRENT_USER_GROUP=
OPT_QUIET=

while getopts "hu:g:v:p:nkd:q" OPTION; do
    case $OPTION in
        h) usage ; exit 0 ;;
        u) OPT_AUSER=$OPTARG ;;
        g) OPT_AGROUP=$OPTARG ;;
        v) OPT_VIRTUALS=$OPTARG ;;
        p) OPT_PHPCLI=$OPTARG ;;
        n) OPT_USE_CURRENT_USER_GROUP=1 ;; # Actually guess from context for historical reasons
        k) OPT_GUESS_USER_GROUP_FROM_ROOT=1 ;; # Overrides -n user and group values
        d) set_debug ;;
        q) OPT_QUIET="-q" ;;
        ?) usage ; exit 1 ;;
    esac
    if [ -n "$OPT_PHPCLI" ]; then
        PHPCLI=$(which "${OPT_PHPCLI}" || command -v "${OPT_PHPCLI}")
        if [ -z "$PHPCLI" ]; then
            echo "PHP command: ${OPT_PHPCLI} not found. Please provide an existing command."
            exit 1
        fi
        #echo "PHP command: ${PHPCLI}"
    fi
    if [ ${DEBUG} = '1' ] ; then
        if [ ${ECHOFLAG} = '1' ] ; then
            ECHOFLAG=0
            echo ${DEBUG_PREFIX}
        fi
        OUTPUT="option: -${OPTION}"
        if [ -n "${OPTARG}" ] ; then
            OUTPUT="${OUTPUT} ${OPTARG}"
        fi
        echo ${DEBUG_PREFIX} "${OUTPUT}"
    fi
done
shift $((OPTIND - 1))

# define command to execute for main program
if [ -z "$1" ]; then
    COMMAND="default"
    EXITONFAIL="n"
else
    COMMAND=$1
    EXITONFAIL="y"
fi

if [ ${DEBUG} = '1' ] ; then
    echo ${DEBUG_PREFIX}
    echo ${DEBUG_PREFIX} COMMAND: "${COMMAND}"
fi

if [ ${DEBUG} = '1' ] ; then
    echo ${DEBUG_PREFIX}
    echo ${DEBUG_PREFIX} usage output: begin
    usage
    echo ${DEBUG_PREFIX} usage output: end
    #echo ${DEBUG_PREFIX}
fi

# part 2 - distribution check
# ---------------------------

AUSER=nobody
AGROUP=nobody
VIRTUALS=""
USER=$(whoami)

check_distribution() {
if [ -f /etc/debian_version ]; then
    AUSER=www-data
    AGROUP=www-data
elif [ -f /etc/redhat-release ]; then
    AUSER=apache
    AGROUP=apache
elif [ -f /etc/gentoo-release ]; then
    AUSER=apache
    AGROUP=apache
elif [ -f /etc/SuSE-release ]; then
    AUSER=wwwrun
    AGROUP=wwwrun
else
    UNAME=$(uname | cut -c 1-6)
    if [ "$UNAME" = "CYGWIN" ]; then
        AUSER=SYSTEM
        AGROUP=SYSTEM
    elif [ "$UNAME" = "Darwin" ]; then
        AUSER=_www
        AGROUP=_www
    elif [ "$UNAME" = "FreeBS" ]; then
        AUSER=www
        AGROUP=www
    fi
fi
}

check_hosting_platform() {
    # check if looks like a virtualmin, where sites are hosted within the home folder
    if [ -f /usr/sbin/virtualmin ]; then
        # shellcheck disable=SC3028,SC3054
        CURRENT_SCRIPT=$(readlink -f "$0")
        DIR_PATH=$(cd "$(dirname "$CURRENT_SCRIPT")" && pwd)
        CURRENT_SCRIPT_PATH="$DIR_PATH/$(basename "$CURRENT_SCRIPT")"

        if echo "$CURRENT_SCRIPT_PATH" | ${GREP} -q "home/[^/]*"; then
            AUSER=$(echo "$CURRENT_SCRIPT_PATH" | ${GREP} -o "home/[^/]*" | ${CUT} -d "/" -f 2)
            AGROUP=$(/usr/bin/id -gn "$AUSER")
        fi
    fi
}

check_webroot() {
    AUSER=$(stat -c "%U" .)
    AGROUP=$(stat -c "%G" .)
}

if [ -z "${OPT_GUESS_USER_GROUP_FROM_ROOT}" ]; then
    check_distribution
    check_hosting_platform
else
    check_webroot
fi

# part 3 - default and writable subdirs
# -------------------------------------
# shellcheck disable=SC2153
DIR_LIST_DEFAULT="${ADMIN_PATH} ${TIKI_CONFIG_PATH} ${DOC_PATH} ${DEPRECATED_FILES_PATH} ${STATIC_IMG_PATH} ${INSTALLER_PATH} ${LANG_SRC_PATH} ${LIB_PATH} ${MODULES_PATH} ${PERMISSIONCHECK_PATH} ${DEPRECATED_STORAGE_PATH} ${TEMP_PATH} ${SMARTY_TEMPLATES_PATH} ${TESTS_PATH} ${BASE_THEMES_SRC_PATH} ${TIKI_TESTS_PATH} ${TIKI_VENDOR_NONBUNDLED_PATH} ${WHELP_PATH}"

DIR_LIST_WRITABLE="${TIKI_CONFIG_PATH} ${DEPRECATED_IMG_WIKI_PATH} ${DEPRECATED_IMG_WIKI_UP_PATH} ${TRACKER_FIELD_IMAGE_STORAGE_PATH} ${DEPRECATED_STORAGE_PATH} ${STORAGE_PUBLIC_PATH} ${DEPRECATED_STORAGE_PUBLIC_H5P_PATH} ${FILE_GALLERY_DEFAULT_STORAGE_PATH} ${TEMP_PATH} ${TEMP_CACHE_PATH} ${TEMP_HTTP_PUBLIC_PATH} ${SMARTY_COMPILED_TEMPLATES_PATH} ${SMARTY_TEMPLATES_PATH} ${BASE_THEMES_SRC_PATH} ${WHELP_PATH} ${DEPRECATED_MODS_PATH} ${DEPRECATED_FILES_PATH} ${TIKI_TESTS_PATH} ${UNIFIED_INDEX_TEMP_PATH} ${TIKI_VENDOR_NONBUNDLED_PATH} ${TIKI_VENDOR_CUSTOM_PATH}"

DIRS=${DIR_LIST_WRITABLE}

# part 4 - several functions
# --------------------------

# part 4.1 - several functions as permission settings for different usecases

dec2oct() {
    #DEC_IN=85
    #
    #
    #
    R8=$(( ${DEC_IN} % 8 ))
    R8=$((DEC_IN % 8))
    O1=${R8}
    IN=$(( ${DEC_IN} - ${R8} ))
    #
    #echo foo ${IN}
    #
    DEC_IN=${IN}
    R64=$(( ${DEC_IN} % 64 ))
    O2=$(( ${R64} / 8 ))
    IN=$(( ${DEC_IN} - ${R64} ))
    #
    #echo bar ${IN}
    #
    DEC_IN=${IN}
    R512=$(( ${DEC_IN} % 512 ))
    O3=$(( ${R512} / 64 ))
    #
    #echo ${R512} ${R64} ${R8}
    #
    OCT_OUT=${O3}${O2}${O1}
}

dec2oct_test() {
    DEC_IN=$(( 0500 | 0220 ))
    dec2oct
    echo "${OCT_OUT}"
    echo break
    exit 1
}
#dec2oct_test

debug_breakpoint() {
    echo
    echo "debug breakpoint"
    exit 1

}

# debug exit
debug_exit() {
if [ "${DEBUG}" = '1' ] ; then
    echo
    echo "Exiting... for execution mode use option '-d off' or set DEBUG=0 at the beginning of this script"
    echo
    exit 1
fi
}

get_permission_data() {
    if [ ${DEBUG} = '1' ] ; then
        echo ${DEBUG_PREFIX}
        echo ${DEBUG_PREFIX} permissioncheck subdir: ${PERMISSIONCHECK_DIR}
    fi
    if [ -d ${USE_CASES_PATH} ] ; then
        if [ -f ${USE_CASES_NAME} ] ; then
            NO_MATCH=999
            MODEL_NAME=${NO_MATCH}
            MODEL_PERMS_SUBDIRS=${NO_MATCH}
            MODEL_PERMS_FILES=${NO_MATCH}
            while IFS= read -r ONE_USE_CASE_PER_LINE; do
                USE_CASE=$(echo "${ONE_USE_CASE_PER_LINE}" | cut -d: -f1)
                if [ "${USE_CASE}" = "${COMMAND}" ] ; then
                    MODEL_NAME=${USE_CASE}
                    MODEL_PERMS_SUBDIRS=$(echo "${ONE_USE_CASE_PER_LINE}" | cut -d: -f2)
                    MODEL_PERMS_FILES=$(echo "${ONE_USE_CASE_PER_LINE}" | cut -d: -f3)
                    MODEL_PERMS_WRITE_SUBDIRS=$(echo "${ONE_USE_CASE_PER_LINE}" | cut -d: -f4)
                    MODEL_PERMS_WRITE_FILES=$(echo "${ONE_USE_CASE_PER_LINE}" | cut -d: -f5)
                    if [ ${DEBUG} = '1' ] ; then
                        echo ${DEBUG_PREFIX}
                        echo ${DEBUG_PREFIX} MODEL_NAME="${MODEL_NAME}"
                        echo ${DEBUG_PREFIX} MODEL_PERMS_SUBDIRS="${MODEL_PERMS_SUBDIRS}"
                        echo ${DEBUG_PREFIX} MODEL_PERMS_FILES="${MODEL_PERMS_FILES}"
                        echo ${DEBUG_PREFIX} MODEL_PERMS_WRITE_SUBDIRS="${MODEL_PERMS_WRITE_SUBDIRS}"
                        echo ${DEBUG_PREFIX} MODEL_PERMS_WRITE_FILES="${MODEL_PERMS_WRITE_FILES}"
                    fi
                fi
            done < ${USE_CASES_NAME}
            if [ "${MODEL_NAME}" = ${NO_MATCH} ] ; then
                    echo no matching use case found
                    exit 1
            fi
        else
            echo ${USE_CASES_NAME} does not exist
            exit 1
        fi
    else
        echo ${USE_CASES_PATH} does not exist
        exit 1
    fi
}

set_permission_dirs_special_write() {
    # function must be defined before set_permission_data
    for WRITABLE in $DIRS ; do
        if [ -d "${WRITABLE}" ] ; then
            if [ "${DEBUG}" = '1' ] ; then
                echo "${DEBUG_PREFIX}"
                echo "${DEBUG_PREFIX}" "${FIND} ${WRITABLE} -type d -exec ${CHMOD} ${MODEL_PERMS_WRITE_SUBDIRS} {} \;"
                echo "${DEBUG_PREFIX}" "${FIND} ${WRITABLE} -type f -exec ${CHMOD} ${MODEL_PERMS_WRITE_FILES} {} \;"
            fi
            ${FIND} "${WRITABLE}" -type d -exec "${CHMOD}" "${MODEL_PERMS_WRITE_SUBDIRS}" {} \;
            ${FIND} "${WRITABLE}" -type f -exec "${CHMOD}" "${MODEL_PERMS_WRITE_FILES}" {} \;
        fi
    done
}

set_permission_data() {
    if [ ${DEBUG} = '1' ] ; then
        echo ${DEBUG_PREFIX} 'for PHP_FILES in "./*.php" ; do'
        echo "${DEBUG_PREFIX}    ${CHMOD} ${MODEL_PERMS_FILES}" "${PHP_FILES}"
        echo ${DEBUG_PREFIX} "done"
        echo ${DEBUG_PREFIX} "${CHMOD} ${MODEL_PERMS_SUBDIRS} ."
    fi
    for PHP_FILES in ./*.php ; do
        ${CHMOD} "${MODEL_PERMS_FILES}" "${PHP_FILES}"
    done
    ${CHMOD} "${MODEL_PERMS_SUBDIRS}" .
    for DEFAULT_DIR in ${DIR_LIST_DEFAULT} ; do
        if [ ${DEBUG} = '1' ] ; then
            echo ${DEBUG_PREFIX}
            echo ${DEBUG_PREFIX} "${FIND} ${DEFAULT_DIR} -type d -exec ${CHMOD} ${MODEL_PERMS_SUBDIRS} {} \;"
            echo ${DEBUG_PREFIX} "${FIND} ${DEFAULT_DIR} -type f -exec ${CHMOD} ${MODEL_PERMS_FILES} {} \;"
        fi
        #debug_breakpoint
        ${FIND} "${DEFAULT_DIR}" -type d -exec "${CHMOD}" "${MODEL_PERMS_SUBDIRS}" {} \;
        ${FIND} "${DEFAULT_DIR}" -type f -exec "${CHMOD}" "${MODEL_PERMS_FILES}" {} \;
        #set_permission_dirs_special_write
    done
    for WRITABLE in $DIRS ; do
        if [ -d "${WRITABLE}" ] ; then
            if [ ${DEBUG} = '1' ] ; then
                echo ${DEBUG_PREFIX}
                echo ${DEBUG_PREFIX} "${FIND} ${WRITABLE} -type d -exec ${CHMOD} ${MODEL_PERMS_WRITE_SUBDIRS} {} \;"
                echo ${DEBUG_PREFIX} "${FIND} ${WRITABLE} -type f -exec ${CHMOD} ${MODEL_PERMS_WRITE_FILES} {} \;"
            fi
            ${FIND} "${WRITABLE}" -type d -exec "${CHMOD}" "${MODEL_PERMS_WRITE_SUBDIRS}" {} \;
            ${FIND} "${WRITABLE}" -type f -exec "${CHMOD}" "${MODEL_PERMS_WRITE_FILES}" {} \;
        fi
    done
}

permission_via_php_check() {
    # model was chosen by Tiki Permission Check (TPC)
    get_permission_data
    # set permissions
#    if [ ${DEBUG} = '2' ] ; then
#        echo
#        ${FIND} . -type d -exec echo ${CHMOD} ${MODEL_PERMS_SUBDIRS} {} \;
#        ${FIND} . -type f -exec echo ${CHMOD} ${MODEL_PERMS_FILES} {} \;
#    fi
    set_permission_data
}

set_permission_data_workaround_general() {
    for DEFAULT_DIR in ${DIR_LIST_DEFAULT} ; do
        # this is quick 'n dirty
        ${CHMOD} -R o+r "${DEFAULT_DIR}"/
        ${FIND} "${DEFAULT_DIR}" -name "*.php" -exec "${CHMOD}" o-r {} \;
        ${FIND} "${DEFAULT_DIR}" -type d -exec "${CHMOD}" o-r {} \;
    done
}

set_permission_data_workaround_sbox() {
    # 500 might not work with .css and images, not yet observed
    #
    # first: classic sbox
    COMMAND="sbox"
    permission_via_php_check
    #
    # second: fix permissions of none-PHP files , really quick 'n dirty
    set_permission_data_workaround_general
    #
    # reset $COMMAND , not really necessary
    COMMAND="sboxworkaround"
}

set_permission_data_workaround_suphp() {
    # 600/601 does not work with .css and images, as observed on Debian Wheezy
    #
    # first: classic paranoia-suphp
    COMMAND="paranoia-suphp"
    permission_via_php_check
    #
    # second: fix permissions of none-PHP files , really quick 'n dirty
    set_permission_data_workaround_general
    #
    # reset $COMMAND , not really necessary
    COMMAND="suphpworkaround"
}

yet_unused_permission_default() {
    ${CHMOD} -fR u=rwX,go=rX .
}

yet_unused_permission_exceptions() {
    ${CHMOD} o-rwx db/local.php
    ${CHMOD} o-rwx db/preconfiguration.php
}

# part 4.2 - composer

# Set-up and execute composer to obtain dependencies
exists()
{
    if type "$1" >/dev/null 2>&1
    then
        return 0
    else
        return 1
    fi
}

composer_core()
{
    if [ -f temp/composer.phar ];
    then
        # todo : if exists php;
        if [ "${LOGCOMPOSERFLAG}" = "0" ] || [ "${LOGCOMPOSERFLAG}" = "2" ] ; then
            "${PHPCLI}" temp/composer.phar self-update --2 ${OPT_QUIET}
            RETURNVAL=$?
        fi
        if [ ${LOGCOMPOSERFLAG} = "1" ] ; then
            "${PHPCLI}" temp/composer.phar self-update --2 ${OPT_QUIET} > ${TIKI_COMPOSER_SELF_UPDATE_LOG}
            RETURNVAL=$?
        fi
        if [ "${RETURNVAL}" -eq 0 ];
        then
            NEED_NEW_COMPOSER="0"
        else
            echo "Composer self-update failed. Reinstalling composer"
            NEED_NEW_COMPOSER="1"
            rm temp/composer.phar
        fi
        # remove previous container.php in case of incompatibility
        rm -f temp/cache/container.php
    else
        NEED_NEW_COMPOSER="1"
    fi

    if [ ${NEED_NEW_COMPOSER} = "1" ];
    then
        if exists curl;
        then
            curl -s https://getcomposer.org/installer | "${PHPCLI}" -- --install-dir=temp --2
        else
            echo "CURL command not found. Trying to obtain the composer executable using PHP."
            # todo : if exists php;
            "${PHPCLI}" -r "eval('?>'.file_get_contents('https://getcomposer.org/installer'));" -- --install-dir=temp --2
        fi
        # if PATCHCOMPOSERFLAG then modify temp/composer.phar to avoid the warnings
        # this hack is not yet possible because of a self signature check in temp/composer.phar
    fi

    if [ ! -f temp/composer.phar ];
    then
        echo "We have failed to obtain the composer executable."
        echo "NB: Maybe you are behind a proxy, just export https_proxy variable and relaunch setup.sh"
        echo "1) Download it from http://getcomposer.org"
        echo "2) Store it in temp/"
        if [ ${EXITONFAIL} = "y" ]; then
            exit 1
        else
            return
        fi
    fi

    N=0
    # todo : move "if exists php;" to function composer

    # check if we are in development mode so we can prevent uninstalling of development files
    DEVELOPMENT="--no-dev"
        if [ -d vendor_bundled/vendor/phpunit ]; then
            DEVELOPMENT=""
        fi

    if exists php;
    then
        if [ ${LOGCOMPOSERFLAG} = "0" ] ; then
            #until php -dmemory_limit=-1 temp/composer.phar install --working-dir vendor_bundled --prefer-dist --no-dev
            until "${PHPCLI}" -dmemory_limit=-1 temp/composer.phar install --working-dir vendor_bundled --prefer-dist --optimize-autoloader --no-interaction ${DEVELOPMENT} 2>&1 | sed '/Warning: Ambiguous class resolution/d'
            # setting memory_limit here prevents suhosin ALERT - script tried to increase memory_limit to 536870912 bytes
            do
                if [ $N -eq 7 ];
                then
                    if [ ${EXITONFAIL} = "y" ]; then
                        exit 1
                    else
                        return
                    fi
                else
                    echo "Composer failed, retrying in 5 seconds, for a few times. Hit Ctrl-C to cancel."
                    sleep 5
                fi
                N=$((N+1))
            done
        fi
        if [ ${LOGCOMPOSERFLAG} = "1" ] ; then
            until "${PHPCLI}" -dmemory_limit=-1 temp/composer.phar install --working-dir vendor_bundled --prefer-dist --optimize-autoloader --no-interaction ${DEVELOPMENT} > ${TIKI_COMPOSER_INSTALL_LOG}
            # setting memory_limit here prevents suhosin ALERT - script tried to increase memory_limit to 536870912 bytes
            do
                if [ $N -eq 7 ];
                then
                    if [ ${EXITONFAIL} = "y" ]; then
                        exit 1
                    else
                        return
                    fi
                else
                    echo "Composer failed, retrying in 5 seconds, for a few times. Hit Ctrl-C to cancel."
                    sleep 5
                fi
                N=$((N+1))
            done
        fi
        if [ ${LOGCOMPOSERFLAG} = "2" ] ; then
            printf "Suppress output lines with 'Warning: Ambiguous class resolution'\n..."
            #until php -dmemory_limit=-1 temp/composer.phar install --working-dir vendor_bundled --prefer-dist --no-dev | sed '/Warning: Ambiguous class resolution/d'
            until "${PHPCLI}" -dmemory_limit=-1 temp/composer.phar install --working-dir vendor_bundled --prefer-dist --optimize-autoloader --no-interaction ${DEVELOPMENT}
            # setting memory_limit here prevents suhosin ALERT - script tried to increase memory_limit to 536870912 bytes
            do
                if [ $N -eq 7 ];
                then
                    if [ ${EXITONFAIL} = "y" ]; then
                        exit 1
                    else
                        return
                    fi
                else
                    echo "Composer failed, retrying in 5 seconds, for a few times. Hit Ctrl-C to cancel."
                    sleep 5
                fi
                N=$((N+1))
            done
        fi
    fi
    return
}

composer()
{
    # todo : if exists php;
    # insert php cli version check here
    # http://dev.tiki.org/item4721
    PHP_OPTION="--version"
    # This is silly and will fail for minor version >9, but since we already do worse for the php version... benoitg - 2024-02-12
    TIKI_MIN_PHP_VERSION_NUMERIC=$(echo "${TIKI_MIN_PHP_VERSION}" | ${CUT} -c1,3) # no decimal seperator, no floating point data
    TIKI_TOO_RECENT_PHP_VERSION_NUMERIC=$(echo "${TIKI_TOO_RECENT_PHP_VERSION}" | ${CUT} -c1,3)
    #${PHPCLI} ${PHP_OPTION}
    LOCAL_PHP_VERSION_NUMERIC=$("${PHPCLI}" ${PHP_OPTION} | ${GREP} ^PHP | ${CUT} -c5,7)
    #echo ${LOCAL_PHP_VERSION}
    LIKELY_ALTERNATE_PHP_CLI="php81 ph8.1 php8.1-cli" # These have been known to exist on some hosting platforms
    if [ "${LOCAL_PHP_VERSION_NUMERIC}" -lt "${TIKI_MIN_PHP_VERSION_NUMERIC}" ] || [ "${LOCAL_PHP_VERSION_NUMERIC}" -ge "${TIKI_TOO_RECENT_PHP_VERSION_NUMERIC}" ] ; then
        echo "Wrong PHP version: php${LOCAL_PHP_VERSION_NUMERIC}.  A version >= php${TIKI_MIN_PHP_VERSION_NUMERIC} and <= php${TIKI_TOO_RECENT_PHP_VERSION_NUMERIC} is necessary."
        echo "Searching for typically named alternative PHP version ..."
        for phptry in $LIKELY_ALTERNATE_PHP_CLI; do
            PHPTRY=$(which "${phptry}" || command -v "${phptry}")
            #echo "debug: $PHPTRY"
            if [ -n "${PHPTRY}" ]; then
                echo "... correct PHP version ${phptry} detected and used"
                PHPCLI="${PHPTRY}"
                PHPCLIFOUND="y"
        composer_core
                break
            fi
        done
        if [ -z "${PHPCLIFOUND}" ]; then
            echo "... no alternative php version found."
            echo "Please provide an alternative PHP version with the -p option."
            echo "Example: sh $(basename "$0") -p php${TIKI_MIN_PHP_VERSION_NUMERIC}."
            echo "You can use the command-line command 'php[TAB][TAB]' to find out available versions."
            exit 1
        fi
    else
        echo "Local PHP version ${LOCAL_PHP_VERSION_NUMERIC} >= to required PHP version ${TIKI_MIN_PHP_VERSION_NUMERIC} - good"
        composer_core
    fi
}

http_composer() {
    "${PHPCLI}" doc/devtools/composer_http_mode.php execute "$OPT_QUIET"
}

npm_setup() {

    echo "Running node install and build"
    set -x
    npm install --clean-install --engine-strict
    {
        exitCode=$?
        if [ $exitCode -ne 0 ]; then
            exit 1
        fi
    } >/dev/null 2>&1
    npm run build
    {
        exitCode=$?
        if [ $exitCode -ne 0 ]; then
            exit 1
        fi
    } >/dev/null 2>&1
    set +x
}

build() {
    composer
    npm_setup
}

# part 4.3 - several command options as fix, open, ...

command_fix() {
    if [ "$USER" = 'root' ]; then
        if [ -n "$OPT_AUSER" ]; then
            AUSER=$OPT_AUSER
        elif [ -z "$OPT_USE_CURRENT_USER_GROUP" ]; then
            printf "User [%s]: " "$AUSER"
            read -r REPLY

            if [ -n "$REPLY" ]; then
                AUSER=$REPLY
            fi
        fi
    else
        if [ -z "$OPT_USE_CURRENT_USER_GROUP" ]; then
            echo "You are not root or you are on a shared hosting account. You can now:

1- ctrl-c to break now.

or

2- If you press enter to continue, you will probably get some error messages
but it (the script) will still fix what it can according to the permissions
of your user. This script will now ask you some questions. If you don't know
what to answer, just press enter to each question (to use default value)"

            printf "> Press enter to continue: "
            read -r WAIT
            
            AUSER=$USER
        fi
    fi

    if [ -n "$OPT_AGROUP" ]; then
        AGROUP=$OPT_AGROUP
    elif [ -z "$OPT_USE_CURRENT_USER_GROUP" ]; then
        printf "> Group [%s]: " "$AGROUP"
        read -r REPLY
            
        if [ -n "$REPLY" ]; then
            AGROUP=$REPLY
        fi
    fi

    ${TOUCH} db/virtuals.inc
    if [ -n "$OPT_VIRTUALS" ]; then
        VIRTUALS=$OPT_VIRTUALS
    elif [ -n "$OPT_USE_CURRENT_USER_GROUP" ]; then
        VIRTUALS=$(cat db/virtuals.inc)
    else
        VIRTUALS_CONTENTS="$($CAT -s db/virtuals.inc | $TR '\n' ' ')"
        printf "> Multi [%s]: " "$VIRTUALS_CONTENTS"
        read -r VIRTUALS
        [ -z "$VIRTUALS" ] && VIRTUALS=$(${CAT} db/virtuals.inc)
    fi

    if [ -n "$VIRTUALS" ]; then
        for vdir in $VIRTUALS; do
            echo "$vdir" >> db/virtuals.inc
            # cat db/virtuals.inc | sort | uniq > db/virtuals.inc_new @Useless cat. Consider 'cmd < file | ..' or 'cmd file | ..' instead.
            sort < db/virtuals.inc | uniq > db/virtuals.inc_new
            rm -f db/virtuals.inc && mv db/virtuals.inc_new db/virtuals.inc
        done
    fi

    # make sure composer files can be created by tiki-admin.php?page=packages
    echo "Checking non-bundled composer : "
    if [ ! -f composer.json ]; then
        printf " Creating composer.json..."
        cp composer.json.dist composer.json
    else
        printf " Found composer.json..."
    fi
    if [ ! -f composer.lock ]; then
        printf " created composer.lock..."
        echo "{}" > composer.lock
    else
        printf " found composer.json..."
    fi
    echo " done."

    echo "Checking dirs : "
    for dir in $DIRS; do
        printf "  %s ... " "$dir"
        if [ ! -d "$dir" ]; then
            printf " Creating directory"
            ${MKDIR} -p "$dir"
        fi
        echo " ok."
        if [ -n "$VIRTUALS" ] && [ "$dir" != "temp/unified-index" ]; then
            for vdir in $VIRTUALS; do
                printf "  %s/%s ... " "$dir" "$vdir"
                if [ ! -d "$dir/$vdir" ]; then
                    printf " Creating Directory"
                    ${MKDIR} -p "$dir/$vdir"
                fi
                echo " ok."
            done
        fi
    done

    # Check that the USER is in AGROUP
    USERINAGROUP="no"
    for grp in $(${ID} -Gn "$USER"); do
        if [ "$grp" = "$AGROUP" ]; then
            USERINAGROUP="yes"
        fi
    done

    echo "Fix global perms ..."
    if [ "$USER" = 'root' ]; then
        #chown -fR $AUSER:$AGROUP . || echo "Could not change ownership to $AUSER"
        printf "Change user to %s and group to %s..." "$AUSER" "$AGROUP"
        ${CHOWN} -fR "$AUSER":"$AGROUP" .
        echo " done."
    else
        if [ -n "$OPT_AUSER" ]; then
            echo "You are not root. We will not try to change the file owners."
        fi
        if [ "$USERINAGROUP" = "yes" ]; then
            printf "Change group to %s ..."  "$AGROUP"
            chgrp -Rf "$AGROUP" .
            echo " done."
        else
            echo "You are not root and you are not in the group $AGROUP. We can't change the group ownership to $AGROUP."
            echo "Special dirs permissions will be set accordingly."
        fi
    fi

#    find . ! -regex '.*^\(devtools\).*' -type f -exec chmod 644 {} \;
#    echo -n " files perms fixed ..."
#    find . -type d -exec chmod 755 {} \;
#    echo " dirs perms fixed ... done"

    printf "Fix normal dirs ..."
    ${CHMOD} -fR u=rwX,go=rX .
    echo " done."

    printf "Fix special dirs ..."
    if [ "$USER" = 'root' ] || [ "$USERINAGROUP" = "yes" ]; then
        for dir in $DIRS; do
            ${CHMOD} -R g+w "$dir"
        done
        ${CHMOD} g+w composer.json
        ${CHMOD} g+w composer.lock
    else
        for dir in $DIRS; do
            ${CHMOD} -fR go+w "$dir"
        done
        ${CHMOD} go+w composer.json
        ${CHMOD} go+w composer.lock
    fi

#    chmod 664 robots.txt tiki-install.php

    echo " done."

    if [ -n "$OPT_USE_CURRENT_USER_GROUP" ]; then
        composer
    fi
}

command_nothing() {
    echo 'Nothing done yet'
    echo "Try 'sh setup.sh fix' for classic default behaviour or 'sh setup.sh -h' for help."
}

command_open() {
    if [ "$USER" = 'root' ]; then
        if [ -n "$OPT_AUSER" ]; then
            AUSER=$OPT_AUSER
        elif [ -z "$OPT_USE_CURRENT_USER_GROUP" ]; then
            printf "User [%s]: " "$AUSER"
            read -r REPLY
            if [ -n "$REPLY" ]; then
                AUSER=$REPLY
            fi
        fi
        chown -R "$AUSER" .
    else
        echo "You are not root or you are on a shared hosting account. We will not try to change the file owners."
    fi

    chmod -R a=rwX .

    echo " done"

    if [ -n "$OPT_USE_CURRENT_USER_GROUP" ]; then
        composer
    fi
}

set_group_minus_execute() {
    ${CHMOD} -R g-x .
}

set_group_minus_read() {
    ${CHMOD} -R g-r .
}

set_group_minus_write() {
    ${CHMOD} -R g-w .
}

set_group_plus_execute() {
    ${CHMOD} -R g+x .
}

set_group_plus_read() {
    ${CHMOD} -R g+r .
}

set_group_plus_write() {
    ${CHMOD} -R g+w .
}

set_other_minus_execute() {
    ${CHMOD} -R o-x .
}

set_other_minus_read() {
    ${CHMOD} -R o-r .
}

set_other_minus_write() {
    ${CHMOD} -R o-w .
}

set_other_plus_execute() {
    ${CHMOD} -R o+x .
}

set_other_plus_read() {
    ${CHMOD} -R o+r .
}

set_other_plus_write() {
    ${CHMOD} -R o+w .
}

set_user_minus_write() {
    ${CHMOD} -R u-w .
}

set_user_plus_execute() {
    ${CHMOD} -R u+x .
}

set_user_plus_read() {
    ${CHMOD} -R u+r .
}

set_user_plus_write() {
    ${CHMOD} -R u+w .
}

special_dirs_set_permissions_files() {
    for WRITABLE in $DIRS ; do
        if [ -d "${WRITABLE}" ] ; then
            if [ ${DEBUG} = '1' ] ; then
                echo ${DEBUG_PREFIX}
                echo ${DEBUG_PREFIX} "${FIND} ${WRITABLE} -type f -exec ${CHMOD} ${MODEL_PERMS_WRITE_FILES} {} \;"
            fi
            ${FIND} "${WRITABLE}" -type f -exec "${CHMOD}" ${MODEL_PERMS_WRITE_FILES} {} \;
        fi
    done
}

special_dirs_set_permissions_subdirs() {
    for WRITABLE in $DIRS ; do
        if [ -d "${WRITABLE}" ] ; then
            if [ ${DEBUG} = '1' ] ; then
                echo ${DEBUG_PREFIX}
                echo ${DEBUG_PREFIX} "${FIND} ${WRITABLE} -type d -exec ${CHMOD} ${MODEL_PERMS_WRITE_SUBDIRS} {} \;"
            fi
            ${FIND} "${WRITABLE}" -type d -exec "${CHMOD}" ${MODEL_PERMS_WRITE_SUBDIRS} {} \;
        fi
    done
}

special_dirs_set_group_minus_write_files() {
    MODEL_PERMS_WRITE_FILES='g-w'
    special_dirs_set_permissions_files
}

special_dirs_set_group_minus_write_subdirs() {
    MODEL_PERMS_WRITE_SUBDIRS='g-w'
    special_dirs_set_permissions_subdirs
}

special_dirs_set_group_minus_write() {
    #order: 1. files 2. subdirs
    special_dirs_set_group_minus_write_files
    special_dirs_set_group_minus_write_subdirs
}

special_dirs_set_group_plus_write_files() {
    MODEL_PERMS_WRITE_FILES='g+w'
    special_dirs_set_permissions_files
}

special_dirs_set_group_plus_write_subdirs() {
    MODEL_PERMS_WRITE_SUBDIRS='g+w'
    special_dirs_set_permissions_subdirs
}

special_dirs_set_group_plus_write() {
    #order: 1. subdirs 2. files
    special_dirs_set_group_plus_write_subdirs
    special_dirs_set_group_plus_write_files
}

special_dirs_set_other_minus_write_files() {
    MODEL_PERMS_WRITE_FILES='o-w'
    special_dirs_set_permissions_files
}

special_dirs_set_other_minus_write_subdirs() {
    MODEL_PERMS_WRITE_SUBDIRS='o-w'
    special_dirs_set_permissions_subdirs
}

special_dirs_set_other_minus_write() {
    #order: 1. files 2. subdirs
    special_dirs_set_other_minus_write_files
    special_dirs_set_other_minus_write_subdirs
}

special_dirs_set_other_plus_write_files() {
    MODEL_PERMS_WRITE_FILES='o+w'
    special_dirs_set_permissions_files
}

special_dirs_set_other_plus_write_subdirs() {
    MODEL_PERMS_WRITE_SUBDIRS='o+w'
    special_dirs_set_permissions_subdirs
}

special_dirs_set_other_plus_write() {
    #order: 1. subdirs 2. files
    special_dirs_set_other_plus_write_subdirs
    special_dirs_set_other_plus_write_files
}

special_dirs_set_user_minus_write_files() {
    MODEL_PERMS_WRITE_FILES='u-w'
    special_dirs_set_permissions_files
}

special_dirs_set_user_minus_write_subdirs() {
    MODEL_PERMS_WRITE_SUBDIRS='u-w'
    special_dirs_set_permissions_subdirs
}

special_dirs_set_user_minus_write() {
    #order: 1. files 2. subdirs
    special_dirs_set_user_minus_write_files
    special_dirs_set_user_minus_write_subdirs
}

special_dirs_set_user_plus_write_files() {
    MODEL_PERMS_WRITE_FILES='u+w'
    special_dirs_set_permissions_files
}

special_dirs_set_user_plus_write_subdirs() {
    MODEL_PERMS_WRITE_SUBDIRS='u+w'
    special_dirs_set_permissions_subdirs
}

special_dirs_set_user_plus_write() {
    #order: 1. subdirs 2. files
    special_dirs_set_user_plus_write_subdirs
    special_dirs_set_user_plus_write_files
}

permission_via_php_check_menu() {
    echo
    ${CAT}<<EOF
 predefined Tiki Permission Check models:
 ----------------------------------------

 1 paranoia
 2 paranoia-suphp                        w suphp workaround
 3 sbox                                  W sbox workaround
 4 mixed
 5 worry                                 6 moreworry
 7 pain                                  8 morepain
 9 risky                                 a insane

 More documentation about this: https://doc.tiki.org/Permission+Check

 S clear screen

EOF
}

tiki_setup_default_menu() {
    echo
    ${CAT}<<EOF
 Tiki setup.sh - your options
 ============================

Composer: If you are installing via a released Tiki package (zip, tar.gz, tar.bz2, 7z), you can and should skip using Composer. If you are installing
and upgrading via GIT, you need to run Composer after 'git clone' and 'git pull'. More info at https://doc.tiki.org/Composer

Database: Please note that the Tiki schema does not support downgrading the database. It can only perform upgrades. For more info, you may check the database status by opening the tiki-check page (/tiki-check.php).

 b run all necessary setup and exit (composer, npm, etc.) (recommended to be done first except when installing from a .tar)
 c run composer (log output on screen, not all warnings) and exit
 L run composer (log output to logfile) and exit (recommended to be done first)
 V run composer (verbose log output on screen) and exit (recommended to be done first)
 H make composer download packages over HTTP and return here
 n run npm (install and build) and exit

For all Tiki instances (via GIT or via a released package):

 f fix file & directory permissions (classic default)          o open file and directory permissions (classic option)
 S clear screen

 q quit                                  x exit

 m display more directory permissions commands recommended for advanced users only.
   More documentation about this: https://doc.tiki.org/Permission+Check

EOF
}

tiki_setup_default() {
    dummy=foo
    #WHAT='f' # old default
    WHAT=${DEFAULT_WHAT} # composer is recommended in case of an svn checkout
    while true
    do
        if [ "${COMMAND}" != "more-TPC-options" ] ; then
            tiki_setup_default_menu
        else
            permission_via_php_check_menu
            COMMAND="nothing"
        fi
        printf "Your choice [%s]? " "$WHAT"
        read -r INPUT
        if [ -z "${INPUT}" ] ; then
            DUMMY=foo
        else
            OLDWHAT=${WHAT}
            WHAT=${INPUT}
        fi
        case ${WHAT} in
            0)    WHAT=${DEFAULT_WHAT} ; COMMAND="php" ; permission_via_php_check ;;
            1)    WHAT=${DEFAULT_WHAT} ; COMMAND="paranoia" ; permission_via_php_check ;;
            2)    WHAT=${DEFAULT_WHAT} ; COMMAND="paranoia-suphp" ; permission_via_php_check ;;
            3)    WHAT=${DEFAULT_WHAT} ; COMMAND="sbox" ; permission_via_php_check ;;
            4)    WHAT=${DEFAULT_WHAT} ; COMMAND="mixed" ; permission_via_php_check ;;
            5)    WHAT=${DEFAULT_WHAT} ; COMMAND="worry" ; permission_via_php_check ;;
            6)    WHAT=${DEFAULT_WHAT} ; COMMAND="moreworry" ; permission_via_php_check ;;
            7)    WHAT=${DEFAULT_WHAT} ; COMMAND="pain" ; permission_via_php_check ;;
            8)    WHAT=${DEFAULT_WHAT} ; COMMAND="morepain" ; permission_via_php_check ;;
            9)    WHAT=${DEFAULT_WHAT} ; COMMAND="risky" ; permission_via_php_check ;;
            a)    WHAT=${DEFAULT_WHAT} ; COMMAND="insane" ; permission_via_php_check ;;
            w)    WHAT=${DEFAULT_WHAT} ; COMMAND="suphpworkaround" ; set_permission_data_workaround_suphp ;;
            W)    WHAT=${DEFAULT_WHAT} ; COMMAND="sboxworkaround" ; set_permission_data_workaround_sbox ;;
            S)    WHAT=${OLDWHAT} ; clear ;;
            f)    WHAT=$WHAT_NEXT_AFTER_f ; command_fix ;;
            o)    WHAT=${DEFAULT_WHAT} ; command_open ;;
            b)    WHAT='x' ; build ;;
            n)    WHAT=$WHAT_NEXT_AFTER_c ; npm_setup ;;
            c)    WHAT=$WHAT_NEXT_AFTER_c ; LOGCOMPOSERFLAG="0" ; composer ;;
            C)    WHAT=$WHAT_NEXT_AFTER_c ; LOGCOMPOSERFLAG="0" ; composer ;;
            L)    WHAT=$WHAT_NEXT_AFTER_c ; LOGCOMPOSERFLAG="1" ; composer ;;
            V)    WHAT=$WHAT_NEXT_AFTER_c ; LOGCOMPOSERFLAG="2" ; composer ;;
            H)    WHAT=${DEFAULT_WHAT} ; http_composer ;;
            m)    WHAT=${OLDWHAT} ; COMMAND="more-TPC-options" ;;
            q)    echo ""; exit ;;
            Q)    echo ""; exit ;;
            x)    echo ""; exit ;;
            X)    echo ""; exit ;;
            *)    WHAT='x'; echo 'no such command' ;;
        esac
    done
}

# part 5 - main program
# ---------------------

case ${COMMAND} in
    # free defined
    # default is used if no parameter at command line is given
    default)        tiki_setup_default ;;
    fix)            command_fix ;;
    menu)            tiki_setup_default ;;
    nothing)        command_nothing ;;
    open)            command_open ;;
    # Tiki Permission Check (via PHP)
    insane)            permission_via_php_check ;;
    mixed)            permission_via_php_check ;;
    morepain)        permission_via_php_check ;;
    moreworry)        permission_via_php_check ;;
    pain)            permission_via_php_check ;;
    paranoia)        permission_via_php_check ;;
    paranoia-suphp)        permission_via_php_check ;;
    php)            permission_via_php_check ;;
    risky)            permission_via_php_check ;;
    sbox)            permission_via_php_check ;;
    sboxworkaround)        set_permission_data_workaround_sbox ;;
    suphpworkaround)    set_permission_data_workaround_suphp ;;
    worry)            permission_via_php_check ;;
    # composer
    build)        build ;;
    composer)        composer ;;
    # plain chmod
    gmr)            set_group_minus_read ;;
    gmw)            set_group_minus_write ;;
    gmx)            set_group_minus_execute ;;
    gpr)            set_group_plus_read ;;
    gpw)            set_group_plus_write ;;
    gpx)            set_group_plus_execute ;;
    omr)            set_other_minus_read ;;
    omw)            set_other_minus_write ;;
    omx)            set_other_minus_execute ;;
    opr)            set_other_plus_read ;;
    opw)            set_other_plus_write ;;
    opx)            set_other_plus_execute ;;
    umw)            set_user_minus_write ;;
    upr)            set_user_plus_read ;;
    upw)            set_user_plus_write ;;
    upx)            set_user_plus_execute ;;
    # special chmod
    sdgmw)            special_dirs_set_group_minus_write ;;
    sdgpw)            special_dirs_set_group_plus_write ;;
    sdomw)            special_dirs_set_other_minus_write ;;
    sdopw)            special_dirs_set_other_plus_write ;;
    sdumw)            special_dirs_set_user_minus_write ;;
    sdupw)            special_dirs_set_user_plus_write ;;
    foo)            echo foo ;;
    #*)            echo ${HINT_FOR_USER} ;;
    *)            hint_for_users ;;
esac

exit 0

# EOF
