<script>
    export default {
        name : 'App',
    }
</script>

<script setup>
import RedirectsBuilder from './components/RedirectsBuilder.vue'
const props = defineProps({
    baseUrl: String,
    wikiPages: String,
    blogPosts: String,
    articles: String,
    redirects: String
})
</script>

<template>
    <div>
        <RedirectsBuilder :base-url="props.baseUrl" :redirects="redirects" :wiki-pages="wikiPages" :blog-posts="blogPosts" :articles="articles" />
    </div>
</template>