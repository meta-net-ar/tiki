<div class="nav">
    <a id="notification-link" href="{bootstrap_modal controller=monitor action=unread quantity=6}" title=":{tr}Notifications{/tr}" class="nav-link tips">
        {icon name="notification"}
    </a>
</div>
{if !empty($prefs.monitor_count_refresh_interval)}
    {jq}
    var key = 'notification_count_{{$user|default:anonymous}}';
    $('#notification-link')
    .on('clear-all.monitor.tiki', function () {
        $.localStorage.store(key, {
            count: 0
        });
        $(this).trigger('reload.monitor.tiki');
    })
    .on('clear-one.monitor.tiki', function () {
        var link = this;
        $.localStorage.store(key, {
            count: Math.max(0, parseInt($('.badge', link).text(), 10) -1)
        });
        $(this).trigger('reload.monitor.tiki');
    })
    .on('force-reload.monitor.tiki', function () {
        $.localStorage.store(key, null);
        $(this).trigger('reload.monitor.tiki');
    })
    .on('reload.monitor.tiki', function () {
        var link = this;
        $.localStorage.load(key, function (data) {
            $('.badge', link).remove();
            if (data.count > 0) {
                $('<span class="badge text-bg-danger">').text(data.count).prependTo(link);
            }
        }, function (callback) {
            $.getJSON($.service('monitor', 'unread', {nodata: 1}), function (data) {
                callback({
                    count: data.count
                });
            });
        }, {{$prefs.monitor_count_refresh_interval}});
    }).trigger('reload.monitor.tiki');
    {/jq}
{/if}
