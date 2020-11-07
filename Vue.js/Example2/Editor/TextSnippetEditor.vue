<template>
	<div class="snippets-header editor-snippet-container" ref="editorPageRef">
		<div class="canvas-options">
			<div class="left-side">
				<div class="label-options">
					<span>Font</span>
					<radius-select ref="radiusSelect" :width-body="350">
						<div slot="select-header" class="select-open" @click="$refs.radiusSelect.openDropdown()" slot-scope="props">
							<h3 class="title">{{snippetFontFamily}}</h3>
							<span class="lnr lnr-chevron-down"></span>
						</div>
						<div slot="select-body" class="select-body_container" slot-scope="props" :style="{pointerEvents: lookingFont ? 'none' : 'unset'}">
							<div class="search-wrap">
								<input type="text" placeholder="Font name or type" v-model="props.searchValue" @input="props.onchange">
								<button>Search</button>
								<div class="custom-upload-file">
									<transition name="fade-modal">
										<span v-if="notAllowedFiles" class="error-message-by-uploading">{{errorFontPicker}}</span>
									</transition>
									<input id="upload-font" type="file" ref="fileInput" @change="uploadCustomFonts"/>
									<label for="upload-font">Upload</label>
									<img v-if="fontUploading" src="/img/loading.gif" alt="">
								</div>
							</div>
							<div class="font-wrap">
								<font-picker
									@isLookingFont="(data) => lookingFont = data"
									v-if="props.isOpen"
									:fonts="$props.fonts"
									:fontGroups="$props.fontgroups"
									:searchVal="props.searchValue"
									@removeValue="$refs.radiusSelect.removeValue('')"
									@select-font="fontPickerSelect"
								></font-picker>
							</div>
						</div>
					</radius-select>
				</div>
				<div class="flex-half-part" ref="max-lg-1">
<!--				Setting clone computed for min Font Size-->
					<div class="label-options" ref="fontSizeRef">
						<span>Font Size</span>
						<input-counter
							class-name="linked-font-size-input"
							:value="Math.round(snippetFontSize)"
							:by="'px'"
							:every-step="1"
							:stateProp="'fontSize'"
							:max-value="maxFontSizeValue"
							:min-value="snippetFontSizeLinked && minFontSize || 0"
							@change="({prop, val}) => onSnippetChange(val, prop)"
						></input-counter>
						<span class="advanced-bar" @click="openShrinkUntil">Advanced
							<i :class="`lnr ${!shrinkUntil ? 'lnr-chevron-down' : 'lnr-chevron-up'}`"></i>
						</span>
						<transition name="fade-modal">
							<dropdown-ex
								:offset="{x: 0, y: 16}"
								:width="300"
								direction="left"
								relative-ref="fontSizeRef" v-if="shrinkUntil">
								<div class="dropdown-options shrink-dropDown" slot="main-content" slot-scope="props">
									<div class="linked-options">
										<input-ex
											:emit="['change']"
											label="Automatically Set Smallest Font Size"
											for="shrinkUntil"
											type="checkbox"
											:attr="{checked: snippetFontSizeLinked}"
											@change="linkedFontSizes"
											:name="'shrinkUntil'"></input-ex>

									</div>
									<div class="label-options">
										<span>Shrink Until</span>
										<input-counter
											:disabled="snippetFontSizeLinked"
											:value="snippetMinFontSize"
											:max-value="Math.round(snippetFontSize)"
											:by="'px'"
											:stateProp="'customData'"
											:every-step="1"
											@change="detectMinSizeValue"
										></input-counter>
									</div>
								</div>
							</dropdown-ex>
						</transition>
					</div>
				</div>
				<!--				Fill Color-->
				<radius-select ref="radiusSelectColor" :width-body="250" v-if="convertRGB">
					<div slot="select-header" class="select-open ml-2" @click="$refs.radiusSelectColor.openDropdown()" slot-scope="props">
						<span class="color-picker-circle" :style="{backgroundColor: convertRGB}"></span>
						Color
						<span class="lnr lnr-chevron-down"></span>
					</div>
					<div slot="select-body" class="select-body_container" slot-scope="props" :set="setStatusColorPicker(props.isOpen)">
						<chrome-picker
							:value="colorSnippet"
							@input="updateColor"></chrome-picker>
						<div class="currently-used-colors" v-if="currentlyUsedColors.length">
							<h4>Currently Used</h4>
							<span @click="changeColorFromCurrently(color.fill, 'color', 'currently')" v-for="(color,i) in currentlyUsedColors" :style="{backgroundColor: color.fill}" :key="color.id + i"></span>
						</div>
						<div class="recently-used-colors" v-if="recentlyUsedColors.length">
							<h4>Recently Used</h4>
							<span @click="changeColorFromCurrently(color, 'color', 'recently')" v-for="(color,i) in recentlyUsedColors" :style="{backgroundColor: color}" :key="color + i"></span>
						</div>
					</div>
				</radius-select>
				<div class="stroke-width">
					<!--				Stroke color-->
					<radius-select ref="radiusStrokeColor" :width-body="250" :margin="[0, 10, 0, 10]">
						<div slot="select-header" class="select-open" @click="$refs.radiusStrokeColor.openDropdown()" slot-scope="props">
							<span class="stroke-color-circle" :style="{border: `5px solid ${snippetStroke || defaultOutline}`}"></span>
							Outline
							<span class="lnr lnr-chevron-down"></span>
						</div>
						<div slot="select-body" class="select-body_container" slot-scope="props" :set="setStatusOutlinePicker(props.isOpen)">
							<chrome-picker
								:value="snippetStroke || 'rgba(255, 255, 255, 1)'"
								@input="strokeColor"></chrome-picker>
							<div class="currently-used-colors" v-if="currentlyUsedColorsOutline.length">
								<h4>Currently Used</h4>
								<span @click="changeColorFromCurrently(color.fill, 'outline', 'currently')" v-for="(color,i) in currentlyUsedColorsOutline" :style="{backgroundColor: color.fill}" :key="color.id + i"></span>
							</div>
							<div class="recently-used-colors" v-if="recentlyUsedColorsOutline.length">
								<h4>Recently Used</h4>
								<span @click="changeColorFromCurrently(color, 'outline', 'recently')" v-for="(color,i) in recentlyUsedColorsOutline" :style="{backgroundColor: color}" :key="color + i"></span>
							</div>
						</div>
					</radius-select>
					<!--				Stroke Width-->
					<input-counter
						icon="stroke"
						ref="max-lg-2"
						:value="Math.round(snippetStrokeWidth) || 0"
						:max-value="5"
						:every-step="1"
						:stateProp="'strokeWidth'"
						v-tooltip.top-center="{content: 'Stroke Width', offset: 3}"
						@change="({prop, val}) => onSnippetChange(val, prop)"
					></input-counter>
				</div>

				<div class="btn-part-one" ref="min-lg-max-xl-1">
					<action-buttons
						v-tooltip.top-center="{content: 'Bold', offset: 3}"
						@click="onSnippetChange((snippetFontWeight !== 'bold' ? 'bold' : 'normal'), 'fontWeight')"
						:class="snippetFontWeight === 'bold' && 'active_font_style'">
						<span slot="icon" class="lnr_bold">B</span>
					</action-buttons>
					<action-buttons
						v-tooltip.top-center="{content: 'Italic', offset: 3}"
						@click="onSnippetChange((snippetFontStyle !== 'italic' ? 'italic' : 'normal'), 'fontStyle')"
						:class="snippetFontStyle === 'italic' && 'active_font_style'">
						<span slot="icon" class="lnr_italic"><span class="lnr lnr-italic"></span></span>
					</action-buttons>
					<action-buttons
						v-tooltip.top-center="{content: 'Underline', offset: 3}"
						@click="onSnippetChange((snippetUnderline ? false : true), 'underline')"
						:class="snippetUnderline && 'active_font_style'">
						<span slot="icon" class="lnr_underline"><span class="lnr lnr-underline"></span></span>
					</action-buttons>
				</div>
				<div class="btn-part-two" ref="min-lg-max-xl-2">
					<action-buttons
						v-tooltip.top-center="{content: 'Align Left', offset: 3}"
						@click="onSnippetChange('left', 'textAlign')"
						:class="snippetTextAlign === 'left' && 'active_font_style'">
						<span slot="icon" class="lnr lnr-text-align-left"></span>
					</action-buttons>
					<action-buttons
						v-tooltip.top-center="{content: 'Center', offset: 3}"
						@click="onSnippetChange('center', 'textAlign')"
						:class="snippetTextAlign === 'center' && 'active_font_style'">
						<span slot="icon" class="lnr lnr-text-align-center"></span>
					</action-buttons>
					<action-buttons
						v-tooltip.top-center="{content: 'Align Right', offset: 3}"
						@click="onSnippetChange('right', 'textAlign')"
						:class="snippetTextAlign === 'right' && 'active_font_style'">
						<span slot="icon" class="lnr lnr-text-align-right"></span>
					</action-buttons>
				</div>
				<div class="rotate-custom-counter" ref="min-lg-max-xl-3">
					<input-counter
						ref="max-lg-2"
						:value="Math.round(snippetAngle) || 0"
						:max-value="360.00"
						:by="'°'"
						:every-step="1"
						:stateProp="'angle'"
						v-tooltip.top-center="{content: 'Rotate', offset: 3}"
						@change="({prop, val}) => onSnippetChange(val, prop)"
					></input-counter>
				</div>
			</div>
			<div class="right-side">
				<div class="add-custom-data-wrap">
					<i class="help-wrap fa fa-question"></i>
					<button-ex
						v-tooltip.top-center="{content: 'Ready to personalize your PicSnippet?', offset: 3}"
						:radius="22"
						:icon="{dir: 'right', element: `<span class='lnr ${fallBackSOpen ? 'lnr-chevron-up' : 'lnr-chevron-down'}' style='font-weight: 600;	font-size: 15px; top: 2px; position: relative; left: 3px;'></span>`}"
						:text="'Custom Data'"
						:bg="'#fff'"
						:font-size="16"
						:color="'#0A273B'"
						:class-attr="'hover-btn mobile-size-btn'"
						:size="'md'"
						:shadow="'0 4px 8px 0 rgba(74,216,195,0.35), 3px 5px 16px -3px rgba(10,39,59,0.22)'"
						:border="{size: '2', color: '#4AD8C3'}"
						@onclick="showCustomData"
					></button-ex>
				</div>
			</div>
			<span class="lnr lnr-cog open-mobile-options" @click="openMobileOptions()"></span>
			<div class="mobile-options" ref="mobile-options" :class="{'mobile-options-open': showMobileOptions}"></div>
		</div>
		<transition name="height">
			<div class="data-fallbacks" v-if="fallBackSOpen" :class="fallBackSOpen && 'open_fallback_wrap'">
				<div class="fallbacks-parent" ref="fallbackRef">
					<data-fallback
						:main-data="parametersData"
						v-for="(parameter, index) in parametersData"
						:data="parameter"
						:key="parameter.id + index"
						:index="index"
						:highlight="highlightedParam === parameter.id"
						@removeParam="removeActiveParameter"
						@input="({value, type}) => parameter[type] = value"
						@revertHighlightParam="highlightedParam = ''"
					></data-fallback>
				</div>
				<div class="bar_add_btn">
					<button-ex
						:radius="22"
						:text="'+ Add'"
						:class-attr="'hover-btn'"
						:bg="'#fff'"
						:font-size="16"
						:color="'#0A273B'"
						:size="'md'"
						:shadow="'0 4px 8px 0 rgba(74,216,195,0.35), 3px 5px 16px -3px rgba(10,39,59,0.22)'"
						:border="{size: '2', color: '#4AD8C3'}"
						:margin="[0, 0, 0, 15]"
						@onclick="addFallback"
					></button-ex>
				</div>
			</div>
		</transition>
		<modal name="add-fallback" height="auto" :adaptive="true" scrollable ref="modalToBody">
			<div class="custom-modal-wrap">
				<div class="modal_header">
					<h4>About Custom Data</h4>
				</div>
				<div class="modal_body">
					<p>Text describing custom data. This could also be a video, image, or some other way to communicate what custom data is and how to use it. Text describing custom data. This could also be a video, image, or some other way to communicate what custom data is and how to use it.</p>
				</div>
				<div class="modal_footer to-right">
					<span class="text-underline" @click="dontShowItAgain">Don't show it again</span>
					<button-ex
						:radius="22"
						:text="'Got it'"
						:class-attr="'hover-btn'"
						:bg="'#fff'"
						:font-size="16"
						:color="'#0A273B'"
						:size="'md'"
						:shadow="'0 7px 18px 0 rgba(0,0,0,0.09), 0 4px 8px 0 rgba(102,237,143,0.32)'"
						:border="{size: '2', color: '#66ED8F'}"
						@onclick="hideCustomData"
					></button-ex>
				</div>
			</div>
		</modal>
	</div>
</template>

<script>

import uuid from 'uuid';
import {Parameter} from 'lib/Parameter';
import {Chrome} from 'vue-color';
import FontPicker from './FontPicker';
import log from 'lib/Logger';
import RadiusSelect from './elements/Radius-Select';
import InputCounter from './elements/Input-Counter';
import ActionButtons from './elements/Action-Buttons';
import ButtonEx from '../shared/Button/index';
import InputEx from '../shared/Input/index';
import DataFallback from './elements/DataFallbacks';
import DropDownEx from './elements/DropDown';
import {mapState} from 'vuex';
import {
	rgbConvert,
	hexToRGBA,
	rgbToHex
} from 'helpers/functions';
import {
	DEFAULT_STROKE_COLOR
} from 'helpers/constants';
import Icons from 'components/pic-snippet/Icons';
import VModal from 'vue-js-modal';
import Vue from 'vue';
import VTooltip from 'v-tooltip';

Vue.use(VModal, {dynamic: true, injectModalsContainer: true});
Vue.use(VTooltip);

import {
	STATIC_PARAM_VALUE
} from '../../helpers/constants';
import _mixins from "../../helpers/mixins";
import axios from "axios";
import ClientFontLoader from "./ClientFontLoader";

export default {
	mixins: [_mixins],
	name: 'text-snippet-editor',
	props: [
		'fonts',
		'fontgroups',
		'userId',
		'token',
	],
	components: {
		ButtonEx,
		InputEx,
		DataFallback,
		RadiusSelect,
		InputCounter,
		ActionButtons,
		'chrome-picker': Chrome,
		'font-picker': FontPicker,
		'linked': Icons.linked,
		'unlinked': Icons.unlinked,
		'close-blue': Icons['close-blue'],
		'dropdown-ex': DropDownEx,
	},
	data() {
		return {
			fallBackSOpen: false,
			searchValue: '',
			updatedSnippetColor: null,
			minFontSize: 0,
			minFontSizeDisabled: false,
			parametersData: [],
			showMobileOptions: false,
			countEveryChangeColor: 0,
			countEveryChangeOutline: 0,
			highlightedParam: '',
			notAllowedFiles: false,
			maxFontSizeValue: 80,
			errorFontPicker: '',
			shrinkUntil: false,
			shrinkAllow: false,
			lookingFont: true,
			fontUploading: false,
			defaultOutline: DEFAULT_STROKE_COLOR
		}
	},
	computed: {
		...mapState({
			//Active Snippets klass
			snippetWidth: state => state.editor.activeSnippetData.width,
			snippetHeight: state => state.editor.activeSnippetData.height,
			snippetMaxWidth: state => state.editor.activeSnippetData.maxWidth,
			snippetFontSize: state => state.editor.activeSnippetData.fontSize,
			snippetMinFontSize: state => state.editor.activeSnippetData.minFontSize,
			snippetFontSizeLinked: state => state.editor.activeSnippetData.linked,
			snippetFontStyle: state => state.editor.activeSnippetData.fontStyle,
			snippetFontWeight: state => state.editor.activeSnippetData.fontWeight,
			snippetUnderline: state => state.editor.activeSnippetData.underline,
			snippetTextAlign: state => state.editor.activeSnippetData.textAlign,
			snippetAngle: state => state.editor.activeSnippetData.angle,
			//~~~~~~~~~~~
			selectedSnippetID: function (state) {
				this.updatedSnippetColor = null;
				return state.editor.selectedSnippetID
			},
			// Recently used colors
			recentlyUsedColors: state => state.editor.recentlyUsedColors,
			currentlyUsedColors: state => state.editor.currentlyUsedColors,
			currentlyUsedColorsOutline: state => state.editor.currentlyUsedColorsOutline,
			recentlyUsedColorsOutline: state => state.editor.recentlyUsedColorsOutline,
			parametersArray: state => state.editor.parametersArray,

			// StokePicker
			snippetStroke: state => state.editor.activeSnippetData.stroke,
			snippetStrokeWidth: state => state.editor.activeSnippetData.strokeWidth,

			// Font Picker
			snippetFontFamily: state => state.editor.activeSnippetData.fontFamily,
			customFontsList: state => state.editor.customFontsList,
		}),
		colorSnippet() {
			const snippetText = window.TextSnippetsArray.find((a) => (a.id || a.uuid) === this.selectedSnippetID);
			if (this.updatedSnippetColor) {
				return this.updatedSnippetColor
			} else if (snippetText) {
				snippetText.fill = snippetText.fill || 'rgb(0, 0, 0)';
				return rgbConvert(snippetText.fill)
			}
		},
		convertRGB() {
			if (this.colorSnippet) {
				return `rgb(${this.colorSnippet.r}, ${this.colorSnippet.g}, ${this.colorSnippet.b})`;
			}
		},
	},
	mounted() {
		if (localStorage.getItem(this.$props.userId)) {
			if (JSON.parse(localStorage.getItem(this.$props.userId)).color) {
				this.$store.commit('editor/SET_RECENTLY_USED_COLORS', {
					value: {
						fill: JSON.parse(localStorage.getItem(this.$props.userId)).color,
						userID: this.$props.userId
					}, type: 'color'
				})
			}
			if (JSON.parse(localStorage.getItem(this.$props.userId)).outline) {
				this.$store.commit('editor/SET_RECENTLY_USED_COLORS', {
					value: {
						fill: JSON.parse(localStorage.getItem(this.$props.userId)).outline,
						userID: this.$props.userId
					}, type: 'outline'
				})
			}
		}

		this.mediaQueries();
		window.addEventListener('resize', this.mediaQueries);

		Bus.$on('removeParamsFromHeader', () => {
			this.parametersData = [];
		});

		Bus.$on('parametersData', data => {
			if (data) {
				this.parametersData = data;
			}
		});
		Bus.$on('removeParameterByIndex', (index) => {
			this.removeActiveParameter(index)
		})
		Bus.$on('notifyFallback', (param) => {
			this.highlightedParam = param.idParam;
			this.showCustomData()
		});
	},
	methods: {
		openShrinkUntil() {
			this.shrinkUntil = !this.shrinkUntil;
			this.addRemoveEventOnDocument('closeShrinkModalSelf');
		},
		closeShrinkModalSelf(e) {
			const target = e.target || e.currentTarget;
			this.closeModal(!target.closest('.shrink-dropDown') && !target.closest('.linked-font-size-input'), () => {
				this.shrinkUntil = false;
			})
		},
		uploadCustomFonts(evt) {
			const _validFileExtensions = ["ttf", "otf", "woff"];
			let sFileName = this.$refs.fileInput.value;
			if (sFileName.length > 0) {
				if (!_validFileExtensions.includes(sFileName.split('.').pop())) {
					this.errorFontPicker = 'Allowed extensions are: .ttf, .otf, .woff';
					this.notAllowedFiles = true;
					setTimeout(() => {/*Hide Notification alert after 4s*/
						this.notAllowedFiles = false;
					}, 4000);
					return false;
				}
			}

			let formData = new FormData();
			formData.append('file', evt.target.files[0]);

			const config = {
				headers: {
					'content-type': 'multipart/form-data',
					'X-CSRF-TOKEN': this.$props.token,
				}
			};
			this.fontUploading = true;
			axios.post('/storeFont', formData, config)
				.then((response) => {
					if (!response.data.success) {
						this.errorFontPicker = response.data.message; //The uploaded font is already exist
						this.notAllowedFiles = true;
						this.fontUploading = false;
						setTimeout(() => {/*Hide Notification alert after 4s*/
							this.notAllowedFiles = false;
						}, 4000);
					} else {
						ClientFontLoader.loadFont(null, {
							data: response.data.customFonts,
							path: response.data.fontsPath
						}).then(_ => {
							this.fontUploading = false;
							this.$nextTick(() => {
								this.$store.commit('editor/SET_CUSTOM_FONTS_LIST', response.data.customFonts);
							})
						});
					}
				})
				.catch((error) => {
					console.error(error);
				});
		},
		changeColorFromCurrently(color, type, isRecently) {
			let _color = '';
			if (color.includes('#')) {
				const getRGB = hexToRGBA(color);
				_color = {
					hex: color,
					rgba: {
						r: getRGB[0],
						g: getRGB[1],
						b: getRGB[2],
						a: 1
					}
				}
			} else {
				_color = {
					hex: rgbToHex(color),
					rgba: rgbConvert(color),
				};
			}
			if (type === 'color') {
				this.updateColor(_color, 'noUpdateCurrently', isRecently)
			} else this.strokeColor(_color, 'noUpdateCurrently', isRecently)
		},
		changeColorFromRecently() {
		},
		setStatusColorPicker(status) {
			if (!status) this.countEveryChangeColor = 0;
		},
		setStatusOutlinePicker(status) {
			if (!status) this.countEveryChangeOutline = 0;
		},
		// On Mobile: cutting components and append to mobile wrapper
		mediaQueries() {
			if (window.matchMedia('(max-width: 1400px)').matches) {
				[this.$refs['min-lg-max-xl-1'], this.$refs['min-lg-max-xl-2'], this.$refs['min-lg-max-xl-3']].forEach(element => {
					this.$refs['mobile-options'] && this.$refs['mobile-options'].append(element)
				})
			}
			if (window.matchMedia('(max-width: 991px)').matches) {
				[this.$refs['max-lg-1'], this.$refs['max-lg-2'].$el].forEach(element => {
					this.$refs['mobile-options'] && this.$refs['mobile-options'].append(element)
				})
			}
		},
		openMobileOptions() {
			this.showMobileOptions = !this.showMobileOptions;
			this.addRemoveEventOnDocument('closeMobileOptionsSelf');
		},
		closeMobileOptionsSelf(e) {
			const target = e.target || e.currentTarget;
			this.closeModal((!target.closest('.mobile-options') && !target.closest('.linked')), () => {
				this.showMobileOptions = false;
			})
		},
		removeActiveParameter (index) {
			this.parametersData.splice(index, 1);
		},
		linkedFontSizes() {
			this.onSnippetChange(!this.snippetFontSizeLinked, 'linked');
			if (this.snippetFontSizeLinked) {
				const minFontSize = Math.round(this.snippetFontSize / 2);
				this.$store.commit('editor/SET_ACTIVE_SNIPPET_DATA', {'minFontSize': minFontSize});
				this.onSnippetChange(minFontSize, 'minFontSize');
			}
		},
		detectMinSizeValue({val}) {
			this.$store.commit('editor/SET_ACTIVE_SNIPPET_DATA', {'minFontSize': val});
			Bus.$emit('snippetOptionChange', {prop: 'minFontSize', val});
		},
		strokeColor(val, notUpdateCurrently, isRecently) {
			this.onSnippetChange(val.hex, 'stroke');
			isRecently !== 'currently' && this.$store.commit('editor/SET_CURRENTLY_USED_COLORS', {
				value: {
					id: this.selectedSnippetID,
					fill: val.hex
				}, type: 'outline'
			});
			if (!notUpdateCurrently) {
				this.countEveryChangeOutline < 1 && this.$store.commit('editor/SET_RECENTLY_USED_COLORS', {
					value: {
						fill: val.hex,
						userID: this.$props.userId
					}, type: 'outline'
				});
				this.countEveryChangeOutline++;
			}
		},
		updateColor(val, notUpdateCurrently, isRecently) {
			this.updatedSnippetColor = val.rgba;
			this.onSnippetChange(this.convertRGB, 'setColor');
			isRecently !== 'currently' && this.$store.commit('editor/SET_CURRENTLY_USED_COLORS', {
				value: {
					id: this.selectedSnippetID,
					fill: val.hex
				}, type: 'color'
			});
			if (!notUpdateCurrently) {
				this.countEveryChangeColor < 1 && this.$store.commit('editor/SET_RECENTLY_USED_COLORS', {
					value: {
						fill: val.hex,
						userID: this.$props.userId
					}, type: 'color'
				});
				this.countEveryChangeColor++;
			}
		},
		showCustomData() {
			this.$nextTick(() => {
				this.fallBackSOpen = !this.fallBackSOpen;
				Bus.$emit('openFallback', this.fallBackSOpen ? 150 : 0);
				if (!localStorage.getItem('customDataModal')) {
					this.fallBackSOpen && this.$modal.show('add-fallback');
					this.$nextTick(() => {
						const _cloneModal = this.$refs.modalToBody.$refs.overlay;
						if (_cloneModal) {
							this.$refs.modalToBody.$refs.overlay && this.$refs.modalToBody.$refs.overlay.remove();
							document.body.appendChild(_cloneModal);
						}
					})
				}
			})
		},
		dontShowItAgain() {
			localStorage.setItem('customDataModal', 0);/*Disabling opening Modal for next time*/
			this.$modal.hide('add-fallback');
		},
		hideCustomData() {
			this.$modal.hide('add-fallback');
		},
		addFallback() {
			let paramName = STATIC_PARAM_VALUE;
			if (this.parametersData) {
				for (let i = 0; i < this.parametersData.length; i++) { // generating parameter names with serial numbers
					if (this.parametersData.findIndex((item) => item.param_name === paramName) > -1) {
						paramName = STATIC_PARAM_VALUE + (i + 1);
					} else {
						break;
					}
				}
			}
			const param = new Parameter({
				id: uuid.v4(),
				param_name: paramName,
				param_default_value: ' ',
			});
			this.$store.dispatch('editor/addParameter', {param, id: this.selectedSnippetID}).then(() => {
				this.parametersData.push(param);
				Bus.$emit('setOriginalText', param);
				// For Animation Horizontal scrolling
				this.$nextTick(() => {
					this.$refs.fallbackRef.scroll({
						top: 0,
						left: this.$refs.fallbackRef.scrollWidth,
						behavior: 'smooth'
					})
				});
			});
		},
		onSnippetChange(val, prop) {
			// For min Font Size link
			if (prop === 'fontSize' && this.snippetFontSizeLinked && val < this.snippetMinFontSize) {
				return
			} else if (prop === 'fontSize' && this.snippetFontSizeLinked && val > this.snippetMinFontSize) {
				this.$store.commit('editor/SET_ACTIVE_SNIPPET_DATA', {'minFontSize': Math.round(val / 2)});
				Bus.$emit('snippetOptionChange', {prop: 'minFontSize', val: this.snippetMinFontSize});
			}
			//!!!!!!!!!!!!
			this.$store.commit('editor/SET_ACTIVE_SNIPPET_DATA', {[prop]: val});
			Bus.$emit('snippetOptionChange', {prop, val});
		},
		fontPickerSelect(font) {
			log.debug(font);
			this.onSnippetChange(font, 'fontFamily');
		}
	},
	beforeDestroy() {
		if (document.querySelector('.parent-container')) {//If there are still opened dropdown, they should be remove
			document.querySelector('.parent-container').remove();
		}
	},
	destroyed() {
		Bus.$off('parametersData');
		Bus.$off('removeParameterByIndex');
		Bus.$off('notifyFallback');
		window.removeEventListener('resize', this.mediaQueries);
	}
};
</script>
<style scoped lang="scss">
	.label-options {
		position: relative;
		display: flex;
		flex-direction: column;
		align-items: center;
	}
	.label-options > span:not(.advanced-bar) {
		color: #0a273b;
		font-size: 11px;
		position: absolute;
		top: -14px;
		left: 0;
		right: 0;
		margin: auto;
		text-align: center;
	}
	.label-options > span.advanced-bar {
		font-size: 11px;
		position: absolute;
		bottom: -16px;
		cursor: pointer;
		@media screen and (-ms-high-contrast: active), (-ms-high-contrast: none) {
			/* add your IE10-IE11 css here */
			left: 10px;
		}
	}
	.label-options > span.advanced-bar i {
		font-weight: 600;
		font-size: 10px;
	}
	.shrink-dropDown {
		display: flex;
		align-items: center;
		padding: 15px;
		> div.linked-options {
			@media screen and (-ms-high-contrast: active), (-ms-high-contrast: none) {
				/* add your IE10-IE11 css here */
				width: 80%;
			}
		}
	}
	.shrink-dropDown .linked-options >>> .field-control{
		align-items: baseline;
	}
	.shrink-dropDown .linked-options >>> .field-control label {
		font-weight: 400;
	}
	.custom-upload-file {
		position: relative;
		line-height: 34px;
		font-weight: 600;
		height: 36px;
		width: 120px;
		border: 1px solid #0a273b;
		border-radius: 8px;
		background-color: #fdfdfd;
		padding: 0 15px;
		outline: none;
		box-shadow: inset 1px 1px 0 0 rgba(10,39,59,.25), 3px 5px 7px -3px rgba(10,39,59,.24), 0 2px 0 0 rgba(10,39,59,.14), 4px 2px 5px 0 rgba(10,39,59,.14);
		margin-left: 5px;
		cursor: pointer;
		z-index: 2;
		display: flex;
    	align-items: center;
		img {
			width: 15px;
			transform: scale(3.5);
			margin-left: 10px;
			z-index: -1;
		}
		.error-message-by-uploading {
			top: -45px;
			padding: 0px 11px;
			left: 50%;
			transform: translateX(-50%);
			width: fit-content;
			white-space: nowrap;
		}
		input {
			display: none;
		}
		label {
			margin: 0;
			cursor: pointer;
		}
	}
</style>
