<div id="single-spa-application:@vue-mf/comments" class="wp-comments-ux"></div>

{jq}
    window.registerApplication({
    name: "@vue-mf/comments",
    app: () => importShim("@vue-mf/comments"),
    activeWhen: (location) => {
    let condition = true;
    return condition;
    }
    });
    onDOMElementRemoved("single-spa-application:@vue-mf/comments", function () {
    window.unregisterApplication("@vue-mf/comments");
    });
{/jq}
