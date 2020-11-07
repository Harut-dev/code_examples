<template>
	<div class="common-wrap" :class="{'error-wrap': (errors && errors.show), 'warning-wrap': (warnings && warnings.show)}">
		<div :class="[$props.for ? 'label-for' : '', 'field-control']">
			<label v-if="$props.label && !$props.for">{{$props.label}}</label>
			<input
				:type="$props.type"
				:name="$props.name"
				:value="$props.value || ''"
				ref="inputRef"
				:id="$props.for ? $props.for : ''"
				v-multi-event="{ evt: [...$props.emit], fn: emitMethods }"
			/>
			<label :for="$props.for" v-if="$props.for">{{$props.label}}</label>
		</div>
		<div v-if="warnings && warnings.show" class="warning-messages-field">
			<p><i class="help-input fa fa-question"></i>{{warnings.warningMsg}}</p>
		</div>
		<div v-if="errors && errors.show" class="error-messages-field">
			<img src="/img/icons/Error.png" alt="Error">
			<p v-if="errors.inverseError">{{errors.inverseError}}</p>
			<p v-else>{{errors.errorMsg}}</p>
		</div>
	</div>
</template>
<script>
	import Vue from 'vue';
	export  default {
		name: 'Input-ex',
		props: {
			'modelInput': {
				default: '',
				required: false
			},
			'target': {
				type: Boolean,
				default: false,
				required: false
			},
			'type': {
				default: 'text',
				type: String
			},
			'for': {
				default: '',
				type: String
			},
			'name': {
				default: '',
				type: String
			},
			'label': {
				default: '',
				type: String
			},
			'value': {
				required: false,
				default: (message) => {
					return message
				},
			},
			'errors': {
				default: (message) => {
					return message
				},
				required: false,
				type: Object
			},
			'warnings': {
				default: (message) => {
					return message
				},
				required: false,
				type: Object
			},
			'attr': {
				default: (message) => {
					return message
				},
				type: Object
			},
            'autofocus': {
			  default: false,
			  type: Boolean,
              required: false
            },
			'emit': {
				required: false,
				default: arr => arr,
				type: Array
			}
		},
		data () {
			return {
			}
		},
		methods: {
			emitMethods (e) {
				if (this.$props.target) {
					if (this.$props.type === 'checkbox') {
						this.$emit(e.type, e.target.checked);
					} else {
						this.$emit(e.type, e.target.value);
					}
				} else {
					this.$emit(e.type, e);
				}
			}
		},
		mounted () {
          if (this.$props.autofocus) {
		    this.$nextTick(() => {
                this.$refs.inputRef.focus();
            })
          }
          if (this.$props.attr) {
            for (let attr in this.$props.attr) {
				if (this.$props.type === 'checkbox' && !this.$props.attr[attr]) {
					this.$refs.inputRef.removeAttribute(attr);
				} else {
					this.$refs.inputRef.setAttribute(attr, this.$props.attr[attr]);
				}
            }
          }
		}
	}
	function functionWrapper(e) {
		/* add filters to handle event type before propagating to callback function for custom event handler */
		e.target.__handler__.fn(e)
	}
	Vue.directive('multiEvent', {
		bind: function(el, binding, vnode) {
			el.__handler__ = binding.value;
			binding.value.evt.forEach(e => el.addEventListener(e, functionWrapper));
		},
		unbind: function(el, binding) {
			el.__handler__.evt.forEach(e => el.removeEventListener(e, functionWrapper));
			el.__handler__ = null
		}
	})
</script>
<style>
	.field-control label {
		color: #0A273B;
		font-size: 16px;
		font-weight: bold;
	}
	.field-control > input:not([type="checkbox"]):not([type="radio"]) {
		box-sizing: border-box;
		height: 36px;
		border: 1px solid #0A273B;
		border-radius: 8px;
		background-color: #FFFFFF;
		box-shadow: inset 1px 2px 3px 0 rgba(10,39,59,0.25), 3px 5px 16px -3px rgba(10,39,59,0.22);
		margin-bottom: 16px;
		outline: none;
		padding: 0 10px;
	}
	.field-control > input:not([type="checkbox"]):not([type="radio"]):focus {
		border: 2px solid #0A273B;
	}
	.field-control {
		display: flex;
		flex-direction: column;
	}
	.label-for label {
		cursor: pointer;
		margin-bottom: 0;
		margin-left: 10px;
	}
	.label-for input[type=checkbox]:before{
		cursor: pointer;
		font-family: "FontAwesome";
		content: "\f00c";
		font-size: 15px;
		color: transparent !important;
		background: #fff;
		display: block;
		height: 18px;
		width: 18px;
		padding: 1px 12px 0 1px;
		line-height: 14px;
		border: 1px solid #0A273B;
		border-radius: 4px;
	}
	.label-for input[type=checkbox]:checked:before {
		color: black !important;
	}
	.label-for input[type=checkbox] {
		-webkit-appearance: none;
		-o-appearance: none;
		outline: none;
		content: none;
	}
	.label-for {
		display: flex;
		align-items: center;
		flex-direction: row;
	}
	.warning-wrap .field-control > input:not([type="checkbox"]):not([type="radio"]){
		margin-bottom: 3px;
	}
	.error-wrap .field-control > input:not([type="checkbox"]):not([type="radio"]){
		border: 2px solid #f9333c;
		margin-bottom: 3px;
	}
	.common-wrap .error-messages-field > img {
		height: 24px;
		width: 24px;
		margin-right: 8px;
	}
	.common-wrap .error-messages-field, .common-wrap .warning-messages-field {
		display: flex;
		align-items: flex-start;
		margin-bottom: 10px;
		margin-top: 5px;
	}
	.common-wrap .warning-messages-field p .help-input {
		width: 20px;
		height: 20px;
		text-align: center;
		line-height: 20px;
		border: 1px solid;
		border-radius: 50%;
		margin-right: 5px;
	}
	.common-wrap .error-messages-field p,
	.common-wrap .warning-messages-field p
	{
		color: #0A273B;
		margin-bottom: 0;
		font-family: Karla;
		font-size: 16px;
		font-weight: bold;
		line-height: 20px;
	}
</style>
