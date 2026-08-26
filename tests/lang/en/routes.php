<?php

return [
    'about'               => 'about',
    'view'                => 'view/{id}',
    'view_project'        => 'view/{id}/project/{project_id?}',
    'view_with_slug'      => 'posts/{post:slug}',
    'view_category_post'  => 'posts/{category:slug}/{post:slug}',
    'view_post_comment'   => 'posts/{post:slug}/{comment:slug?}',
    'manage'              => 'manage/{file_id?}',
    'hello'               => 'Hello world',
    'test_text'           => 'Test text',
];
