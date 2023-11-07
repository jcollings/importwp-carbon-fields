<?php

use ImportWP\Common\Addon\AddonCustomFieldsApi;
use ImportWP\Common\Addon\AddonCustomFieldSaveResponse;
use ImportWP\Common\Addon\AddonInterface;
use ImportWP\Common\Model\ImporterModel;

iwp_register_importer_addon('Carbon Fields', 'iwp-carbon-fields', function (AddonInterface $addon) {

    if (!defined('Carbon_Fields\VERSION')) {
        return;
    }

    $importer_model = $addon->importer_model();
    switch ($importer_model->getTemplate()) {
        case 'user':
            $fields = iwp_carbon_fields_get_fields('user', 'user_meta');
            break;
        case 'term':
            $taxonomy = $importer_model->getSetting('taxonomy');
            $fields = iwp_carbon_fields_get_fields($taxonomy, 'term_meta');
            break;
        default:
            $post_type = $importer_model->getSetting('post_type');
            $fields = iwp_carbon_fields_get_fields($post_type, 'post_meta');
            break;
    }

    $addon->register_custom_fields('Carbon Fields', function (AddonCustomFieldsApi $api) use ($fields) {

        $api->set_prefix('carbon_field');

        // register fields
        $api->register_fields(function (ImporterModel $importer_model) use ($api, $fields) {

            foreach ($fields as $field) {
                $api->add_field($field['name'], $field['key']);
            }
        });

        $api->save(function (AddonCustomFieldSaveResponse $response, $post_id, $key, $value) use ($fields) {

            $field = iwp_carbon_fields_get_field_by_name($key, $fields);
            if (!$field) {
                return;
            }

            $value = iwp_carbon_fields_process_field($response, $post_id, $field, $value);
            $response->update_meta($post_id, $field['data']->get_name(), $value);
        });
    });
});

function iwp_carbon_fields_get_fields($section, $section_type)
{
    $options = [];

    $repository = \Carbon_Fields\Carbon_Fields::resolve('container_repository');
    $containers = $repository->get_containers($section_type); // term_meta, post_meta, user_meta
    foreach ($containers as $container) {
        $fields = $container->get_fields();
        foreach ($fields as $field) {

            switch ($field->get_type()) {
                case 'file':
                case 'image':
                case 'media_gallery':
                    $type = 'attachment';
                    break;
                default:
                    $type = 'text';
            }

            $options[] = [
                'type' => $field->get_type(),
                'id' => $field->get_base_name(),
                'key' =>  $type . '::' . $field->get_name(),
                'data' => $field,
                'name' => $field->get_label()
            ];
        }
    }

    return $options;
}

function iwp_carbon_fields_get_field_by_name($name, $fields, $col = 'id')
{
    foreach ($fields as $field) {
        if ($field['data']->get_name() === $name) {
            return $field;
        }
    }

    return false;
}

function iwp_carbon_fields_process_field($api, $post_id, $field, $value)
{
    $delimiter = apply_filters('iwp/value_delimiter', ',');
    $delimiter = apply_filters('iwp/carbon_fields/value_delimiter', $delimiter);
    $delimiter = apply_filters('iwp/carbon_fields/' . trim($field['id']) . '/value_delimiter', $delimiter);

    switch ($field['type']) {
        case 'file':
        case 'image':

            if ($post_id) {
                $value = $api->processAttachmentField($value, $post_id, ['settings._return' => 'id-raw']);
            }

            $value = $value[0];

            break;
    }

    return $value;
}
