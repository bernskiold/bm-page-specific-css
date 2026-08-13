import { __ } from '@wordpress/i18n';
import { dispatch, select } from '@wordpress/data';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/editor';
import AceEditor from 'react-ace';

// These has to come after the AceEditor import. Bundling the snippets rather
// than letting Ace resolve them at runtime matters: it fetches them relative to
// the script URL, which does not exist in a build, and the request 404s.
import 'ace-builds/src-noconflict/ext-language_tools';
import 'ace-builds/src-noconflict/mode-css';
import 'ace-builds/src-noconflict/theme-github';
import 'ace-builds/src-noconflict/snippets/css';

export const PANEL_ICON = 'editor-code';

export default function CSSPanel() {
	return (
		<>
			<PluginSidebarMoreMenuItem
				target="bm-page-specific-css-sidebar"
				icon={ PANEL_ICON }
			>
				{ __( 'Page Specific CSS', 'bm-page-specific-css' ) }
			</PluginSidebarMoreMenuItem>

			<PluginSidebar
				name="bm-page-specific-css-sidebar"
				title={ __( 'Page Specific CSS', 'bm-page-specific-css' ) }
				icon={ PANEL_ICON }
			>
				<AceEditor
					onChange={ onChange }
					width="100%"
					height="100vh"
					mode="css"
					theme="github"
					name="bm-page-specific-css"
					fontSize={ 14 }
					tabSize={ 4 }
					showPrintMargin={ false }
					showGutter={ true }
					highlightActiveLine={ true }
					value={ getCSSValue() }
					enableBasicAutocompletion={ true }
					enableLiveAutocompletion={ true }
					enableSnippets={ true }
					editorProps={ { $blockScrolling: true } }
				/>
			</PluginSidebar>
		</>
	);
}

function getCSSValue() {
	const meta = select( 'core/editor' ).getEditedPostAttribute( 'meta' );

	if ( meta && meta.page_specific_styles ) {
		return meta.page_specific_styles;
	}
	return '';
}

function onChange( newValue ) {
	dispatch( 'core/editor' ).editPost( {
		meta: {
			page_specific_styles: newValue,
		},
	} );
}
