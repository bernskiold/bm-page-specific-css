import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	PluginSidebar,
	PluginSidebarMoreMenuItem,
	store as editorStore,
} from '@wordpress/editor';
import AceEditor from 'react-ace';

// These has to come after the AceEditor import. Bundling the snippets rather
// than letting Ace resolve them at runtime matters: it fetches them relative to
// the script URL, which does not exist in a build, and the request 404s.
import 'ace-builds/src-noconflict/ext-language_tools';
import 'ace-builds/src-noconflict/mode-css';
import 'ace-builds/src-noconflict/theme-github';
import 'ace-builds/src-noconflict/snippets/css';

export const PANEL_ICON = 'editor-code';

/**
 * The post meta key the styles are stored in. Must match
 * Plugin::CSS_META_KEY on the PHP side.
 */
export const META_KEY = 'page_specific_styles';

const SIDEBAR_NAME = 'bm-page-specific-css-sidebar';

export default function CSSPanel() {
	// Subscribing through useSelect re-renders the editor when the meta
	// changes from elsewhere (undo/redo, switching revisions, a reload after
	// save). Reading with select() once during render did not.
	const value = useSelect(
		( select ) =>
			select( editorStore ).getEditedPostAttribute( 'meta' )?.[
				META_KEY
			] ?? '',
		[]
	);

	const { editPost } = useDispatch( editorStore );

	const onChange = ( newValue ) => {
		editPost( { meta: { [ META_KEY ]: newValue } } );
	};

	return (
		<>
			<PluginSidebarMoreMenuItem
				target={ SIDEBAR_NAME }
				icon={ PANEL_ICON }
			>
				{ __( 'Page Specific CSS', 'bm-page-specific-css' ) }
			</PluginSidebarMoreMenuItem>

			<PluginSidebar
				name={ SIDEBAR_NAME }
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
					value={ value }
					enableBasicAutocompletion={ true }
					enableLiveAutocompletion={ true }
					enableSnippets={ true }
					editorProps={ { $blockScrolling: true } }
				/>
			</PluginSidebar>
		</>
	);
}
