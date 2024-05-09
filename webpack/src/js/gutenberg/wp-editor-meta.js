const { registerPlugin } = wp.plugins;
const { registerBlockType } = wp.blocks;
const { RichText, InspectorControls } = wp.blockEditor;
const { ToggleControl, PanelBody, PanelRow, CheckboxControl, SelectControl, ColorPicker } = wp.components;

import Component from './fields';

registerPlugin('featured-post-plugin', {
    render() {
        return wp.element.createElement(Component, null);
    }
});