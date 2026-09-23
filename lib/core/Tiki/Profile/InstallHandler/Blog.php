<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tiki_Profile_InstallHandler_Blog extends Tiki_Profile_InstallHandler
{
    public function getData()
    {
        if ($this->data) {
            return $this->data;
        }

        $defaults = [
            'title' => '',
            'description' => '',
            'user' => TikiLib::lib('user')->getDefaultAdminLogin(),
            'public' => 'n',
            'max_posts' => 10,
            'heading' => '',
            'use_title' => 'y',
            'use_title_in_post' => 'y',
            'use_description' => 'y',
            'use_breadcrumbs' => 'n',
            'use_author' => 'n',
            'add_date' => 'n',
            'use_find' => 'y',
            'allow_comments' => 'n',
            'allow_post_categorization' => 'n',
            'show_avatar' => 'n',
            'alwaysOwner' => 'n',
            'post_heading' => '',
            'show_related' => 'n',
            'related_max' => 5,
            'use_excerpt' => 'n',
        ];

        $data = array_merge($defaults, $this->obj->getData());

        $data = Tiki_Profile::convertYesNo($data);

        return $this->data = $data;
    }

    public function canInstall()
    {
        $data = $this->getData();
        if (! isset($data['title'])) {
            return false;
        }

        return true;
    }

    public function doInstall()
    {
        $bloglib = TikiLib::lib('blog');

        $data = $this->getData();

        $this->replaceReferences($data);

        $blogId = $bloglib->replace_blog(
            $data['title'],
            $data['description'],
            $data['user'],
            $data['public'],
            $data['max_posts'],
            0,
            $data['heading'],
            $data['use_title'],
            $data['use_title_in_post'],
            $data['use_description'],
            $data['use_breadcrumbs'],
            $data['use_author'],
            $data['add_date'],
            $data['use_find'],
            $data['allow_comments'],
            $data['allow_post_categorization'],
            $data['show_avatar'],
            $data['alwaysOwner'],
            $data['post_heading'],
            $data['show_related'],
            $data['related_max'],
            $data['use_excerpt']
        );

        return $blogId;
    }

    /**
     * Remove blog
     *
     * @param string $blog
     * @return bool
     */
    public function remove($blog)
    {
        if (! empty($blog)) {
            $bloglib = TikiLib::lib('blog');
            $blog = $bloglib->get_blog_by_title($blog);
            if (! empty($blog['blogId']) && $bloglib->remove_blog($blog['blogId'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get current blog data
     *
     * @param array $blog
     * @return mixed
     */
    public function getCurrentData($blog)
    {
        $blogName = ! empty($blog['title']) ? $blog['title'] : '';
        $blog = explode('$', $blogName);
        $blogName = ! empty($blog[2]) ? $blog[2] : $blogName;
        if (! empty($blogName)) {
            $bloglib = TikiLib::lib('blog');
            $blogData = $bloglib->get_blog_by_title($blogName);
            $blogId = ! empty($blogData['blogId']) ? $blogData['blogId'] : 0;
            if (! empty($blogId)) {
                $blogPost = $bloglib->list_blog_posts($blogId);
                $blogPostData = ! empty($blogPost['data']) ? $blogPost['data'] : [];
                foreach ($blogPostData as $key => $post) {
                    $blogPostData[$key]['images'] = $bloglib->get_post_images($post['postId']);
                }
                $blogData['posts'] = $blogPostData;
                return $blogData;
            }
        }
        return false;
    }
}
