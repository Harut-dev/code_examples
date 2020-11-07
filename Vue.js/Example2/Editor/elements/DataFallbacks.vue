<template>
	<div class="simple-fallback"  ref="parent-param-container" :class="{'active-fallback': activeParamWrapper || $props.highlight, 'conflict-param-name': thereIsParamsConflict}">
		<transition name="fade-modal">
			<div v-if="getParamSameNotify" :style="notificationPos" ref="tooltip-param" class="repeated-params-notification">{{getParamSameNotify}}</div>
		</transition>
		<div class="name-field">
			<input-ex
				:type="'text'"
				:label="'Custom Name'"
				:value="$props.data.param_name"
				:emit="['focus', 'blur', 'input', 'change']"
				@focus="activeParamStatus(true, 'focus')"
				@blur="activeParamStatus(false, 'blur')"
				@change="editParameter('param_name')"
				@input="onParamNameChange($event, 'param_name')"
			></input-ex>
		</div>
		<div class="value-field">
			<input-ex
				:type="'text'"
				:label="'Fallback value'"
				:value="trimValue"
				:emit="['focus', 'blur', 'input', 'change']"
				@focus="activeParamStatus(true, 'focus', trimValue)"
				@change="editParameter('param_default_value')"
				@blur="activeParamStatus(false, 'blur')"
				@input="onParamNameChange($event, 'param_default_value')"
				:autofocus="$props.highlight"
			></input-ex>
		</div>
		<close-dynamic class="close-param-icon"
		   :fill="!activeParamWrapper ? '#E4F6FE' : '#fff'"
		   @click="removeParameter()"/>
	</div>
</template>
<script>
	import { mapState } from 'vuex';
	import axios from 'axios';
	import InputEx from '../../shared/Input';
	import Icons from '../Icons';

	export default {
		name: 'data-fallback',
		components: {
			InputEx,
			'close-dynamic': Icons['close-dynamic'],
		},
		props: {
			'data': {
				default: (data) => data,
				required: true,
				type: Object
			},
			'index': {
				type: Number,
				required: true
			},
			'highlight': {
				type: Boolean,
				required: false
			},
			'main-data': {
				type: Array,
				required: false,
				default: () => []
			}
		},
		data () {
			return {
				activeParamWrapper: false,
				param_name: '',
				param_default_value: '',
				activeSnippetID: null,
				oldValueParamName: '',
				thereIsParamsConflict: '',
				notificationPos: {},
				firstFocusForSpace: false,
				defaultValue: '',
			}
		},
		computed: {
			...mapState({
				selectedSnippetID: state => state.editor.selectedSnippetID
			}),
			getParamSameNotify () {
				if (this.thereIsParamsConflict) {
					this.$nextTick(() => {
						if(this.$refs['tooltip-param']) document.body.appendChild(this.$refs['tooltip-param']);
						const boundingPos = this.$refs['parent-param-container'].getBoundingClientRect();
						const alertPos = this.$refs['tooltip-param'].getBoundingClientRect();
						Object.assign(this.notificationPos, {
							top: boundingPos.y - (alertPos.height + 10) + 'px',
							left: boundingPos.x + (boundingPos.width / 2 - alertPos.width / 2) + 'px'
						});
						this.$forceUpdate();
					})
				}
				return this.thereIsParamsConflict
			},
			trimValue () {
				// Must remove first space during focusing
				return this.firstFocusForSpace ? this.$props.data.param_default_value.replace(' ', '') : this.defaultValue || this.$props.data.param_default_value;
			}
		},
		methods: {
			activeParamStatus (status, type, valueRef) {
				if (!status) this.firstFocusForSpace = false;
				if (valueRef === ' ') {
					this.firstFocusForSpace = true;
				}
				if (type === 'focus') {
					this.oldValueParamName = this.$props.data.param_name;
					this.activeSnippetID = this.selectedSnippetID
				}
				this.activeParamWrapper = status;
				this.$emit('revertHighlightParam');
			},
			removeParameter () {
				this.$store.dispatch('editor/removeParameter', {snippetUuid: this.selectedSnippetID, parameterId: this.$props.data.id}).then(res => {
					return res.json();
				});
				this.$emit('removeParam', this.$props.index);
				Bus.$emit('removeParameter', this.$props.data.param_name);
			},
			changeParamName (event) {
				this.$store.commit('editor/CHANGE_FALLBACK_PARAMS', {id: this.selectedSnippetID, idParam: this.$props.data.id, param: 'param_name', value: event.target.value});
			},
			cleanParamName(paramName) {
				// Remove all non alpha-numeric or _ - chars
				return paramName.replace(/[^A-Za-z0-9_\-]/g, '');
			},
			editParameter (typeInput) {
				if (this.thereIsParamsConflict) {
					this.$emit('input', {value: this.oldValueParamName, type: typeInput});
					this.thereIsParamsConflict = '';
				}
				Bus.$emit('paramChanging', {idParam: this.$props.data.id, param_default_value: this.$props.data.param_default_value || ' ', param_name: this.cleanParamName(this.$props.data.param_name), type: typeInput});
				axios.put(`/api/text-snippet/${this.activeSnippetID}/parameter/${this.$props.data.id}`, this.$props.data.toObject());
			},
			calculateSameParam (value) {
				const findSameParam = this.$props.mainData.find(param => (param.param_name === value && this.$props.data.id !== param.id));
				return this.thereIsParamsConflict = findSameParam ? 'The name of the parameter is duplicated' : '';
			},
			onParamNameChange (event, inputType) {
				this.firstFocusForSpace = false;

				const _matchValue = event.target.value.match(/[^\w\-]/);

				if (_matchValue && inputType === 'param_name') {
					let value = event.target.value.split('');
					value.splice(_matchValue.index, 1);
					event.target.value = value.join('')
				} else {
					this.$emit('input', {value: event.target.value, type: inputType});
					if (event.target.value === '' && inputType === 'param_name') {
						this.thereIsParamsConflict = "Parameter's name field cannot be empty";
						return
					} else if (/^\d+$/.test(event.target.value) && inputType === 'param_name') {
						this.thereIsParamsConflict = "Parameter's name field  cannot include only digits";
						return
					}
					if (inputType === 'param_default_value') this.defaultValue = event.target.value;
					if (inputType === 'param_name') {
						if (this.calculateSameParam(event.target.value)) return;
					}
				}
				this.oldValueParamName = event.target.value;
			}
		}
	}
</script>
<style scoped>
	.data-fallbacks .fallbacks-parent .conflict-param-name {
		background-color: #ff000040 !important;
		border-color: #ff000040 !important;
	}
	.repeated-params-notification:after {
		content: "";
		border-width: 5px 5px 0;
		border-left-color: transparent!important;
		border-right-color: transparent!important;
		border-bottom-color: transparent!important;
		bottom: -5px;
		left: calc(50% - 5px);
		width: 0;
		height: 0;
		border-style: solid;
		position: absolute;
		border-color: #fff;
		z-index: 1;
	}
	.repeated-params-notification {
		position: fixed;
		font-size: 12px;
		text-align: center;
		padding: 5px 10px 4px;
		color: #0a273b;
		background-color: #fff;
		box-shadow: 0 6px 16px -2px rgba(10,39,59,.12);
		border-radius: 20px;
		font-weight: 600;
		width: fit-content;
		word-wrap: break-word;
		z-index: 2222;
		border: 1px solid #b2b2b2;
	}
</style>
