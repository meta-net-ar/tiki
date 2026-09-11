{literal}
    <div style="background-color:${borderColor}">
        <div class="grid-stack-item-content">
            <div>
                <div style="${widgetLabel == "" ? " display:none;" : ""}"><small>${widgetLabel}</small></div>
                <div class="d-flex display-5 gap-1 my-2 justify-content-center">
                    <div data-widget-data-source="${dataSource}" data-widget-type="${widget}"></div>
                    <div style="${dataUnit == "" ? " display:none;" : ""}">${dataUnit}</div>
                </div>
                <canvas data-widget-data-source="${dataSource}" data-widget-type="${widget}"
                    data-pointer-color='${textColor}' data-gauge-bg-color='${bgColor}'></canvas>
            </div>
        </div>
    </div>
{/literal}
