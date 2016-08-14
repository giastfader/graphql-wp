<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use \GraphQL\Type\Definition\Type;
use \GraphQL\Type\Definition\ListOfType;
use \GraphQLRelay\Relay;
use \Mohiohio\GraphQLWP\Schema;
use function Stringy\create as s;

class WPPost extends WPInterfaceType {

    const TYPE = 'WP_Post';
    const DEFAULT_TYPE = 'post';

    static function getDescription() {
        return 'The base WordPress post type';
    }

    static function resolveType($obj) {
        \Analog::log('resolving type for '.var_export($obj,true));

        $ObjectType = __NAMESPACE__.'\\'.s($obj->post_type)->upperCamelize();

        \Analog::log('Type is '.$ObjectType);

        if(class_exists($ObjectType)){
            \Analog::log('Returing instance!'.($ObjectType::getInstance())->getName());
            return $ObjectType::getInstance();
        }
    }

    static function getFieldSchema() {
        return [
            'id' => Relay::globalIdField(self::TYPE, function($post){
                return $post->ID;
            }),
            'ID' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The ID of the post',
            ],
            'name' => [
                'type' => Type::string(),
                'description' => 'The post\'s slug',
                'resolve' => function($post) {
                    return $post->post_name;
                }
            ],
            'title' => [
                'type' => Type::string(),
                'description' => 'The title of the post',
                'resolve' => function($post) {
                    return get_the_title($post);
                }
            ],
            'content' => [
                'type' => Type::string(),
                'description' => 'The full content of the post',
                'resolve' => function($post) {
                    return apply_filters('the_content', get_post_field('post_content', $post));
                }
            ],
            'excerpt' => [
                'type' => Type::string(),
                'description' => 'User-defined post except',
                'args' => [
                    'always' => [
                        'type' => Type::boolean(),
                        'desciption' => 'If true will create an excerpt from post content'
                    ]
                ],
                'resolve' => function($post, $args) {

                    $excerpt = apply_filters('the_excerpt',get_post_field('post_excerpt', $post));

                    if(empty($excerpt) && !empty($args['always'])) {
                        $excerpt = apply_filters('the_excerpt', wp_trim_words( strip_shortcodes( $post->post_content )));
                    }

                    return $excerpt;
                }
            ],
            'date' => [
                'type' => Type::string(),
                'description' => 'Format: 0000-00-00 00:00:00',
                'args' => [
                    'format' => ['type' => Type::string()]
                ],
                'resolve' => function($post, $args) {
                    return !empty($args['format']) ? date($args['format'],strtotime($post->post_date)) : $post->post_date;
                }
            ],
            'date_gmt' => [
                'type' => Type::string(),
                'description' => 'Format: 0000-00-00 00:00:00',
                'args' => [
                    'format' => ['type' => Type::string()]
                ],
                'resolve' => function($post, $args) {
                    return !empty($args['format']) ? date($args['format'],strtotime($post->post_date_gmt)) : $post->post_date_gmt;
                }
            ],
            'status' => [
                'type' => PostStatus::getInstance(),
                'description' => 'Status of the post',
                'resolve' => function($post) {
                    return $post->post_status;
                }
            ],
            'parent' => [
                'type' => function() {
                    return static::getInstance();
                },
                'description' => 'Parent of this post',
                'resolve' => function($post) {
                    return $post->post_parent ? get_post($post->post_parent) : null;
                }
            ],
            'modified' => [
                'type' => Type::string(),
                'description' => 'Format: 0000-00-00 00:00:00',
                'args' => [
                    'format' => ['type' => Type::string()]
                ],
                'resolve' => function($post, $args) {
                    return !empty($args['format']) ? date($args['format'],strtotime($post->post_modified)) : $post->post_modified;
                }
            ],
            'modified_gmt' => [
                'type' => Type::string(),
                'description' => 'Format: 0000-00-00 00:00:00',
                'args' => [
                    'format' => ['type' => Type::string()]
                ],
                'resolve' => function($post, $args) {
                    return !empty($args['format']) ? date($args['format'],strtotime($post->post_modified_gmt)) : $post->post_modified_gmt;
                }
            ],
            'comment_count' => [
                'type' => Type::int(),
                'description' => 'Number of comments on post',
                'resolve' => function($post) {
                    return $post->comment_count;
                }
            ],
            'menu_order' => [
                'type' => Type::int(),
                'resolve' => function($post) {
                    return $post->menu_order;
                }
            ],
            'permalink' => [
                'description' => "Retrieve full permalink for current post ",
                'type' => Type::string(),
                'resolve' => function($post) {
                    return get_permalink($post);
                }
            ],
            'terms' => [
                'type' => new ListOfType(WPTerm::getInstance()),
                'description' => 'Terms ( Categories, Tags etc ) or this post',
                'args' => [
                    'taxonomy' => [
                        'description' => 'The taxonomy for which to retrieve terms. Defaults to post_tag.',
                        'type' => Type::string(),
                    ],
                    'orderby' => [
                        'description' => "Defaults to name",
                        'type' => Type::string(),
                    ],
                    'order' => [
                        'description' => "Defaults to ASC",
                        'type' => Type::string(),
                    ]
                ],
                'resolve' => function($post, $args) {

                    $args += [
                        'taxonomy' => null,
                        'orderby'=>'name',
                        'order' => 'ASC',
                    ];
                    extract($args);

                    $res = wp_get_post_terms($post->ID, $taxonomy, ['orderby'=>$orderby,'order'=>$order]);

                    return is_wp_error($res) ? [] : $res;
                }
            ]
        ];
    }
}
