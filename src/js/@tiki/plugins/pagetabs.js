export default function loadTabsContent(tabsKey) {
    const tabs = $(`#nav-${tabsKey} .nav-link`);
    tabs.each(function () {
        $(this).on("show.bs.tab", function () {
            const pageName = $(this).text();
            const pageSelector = pageName.replaceAll(" ", "_");
            const tabPanel = $(`#content${tabsKey}-${pageSelector}`);

            if (!tabPanel.html().trim()) {
                $.tikiModal(tr("Loading..."));
                $.ajax({
                    url: `tiki-index_raw.php?page=${pageName}`,
                    success: function (data) {
                        tabPanel.html(data);
                        $.tikiModal();
                    },
                    error: function () {
                        $.tikiModal();
                    },
                });
            }
        });
    });
}
