<template>
	<div class="main-tags-container" ref="mainTagRef">
		<transition name="fade-modal">
			<p v-if="alreadyExistedTag" class="already-existed-tag">Current tag is already added</p>
		</transition>
		<div class="wrapper-tags">
			<transition-group name="list" tag="div" ref="relativeTagRef">
				<span
					class="added-tag"
					v-for="(tag) in $props.tags"
					:key="tag"
					:style="{width: calcItSelfWidth(tag)}"
				>
					{{tag}}
					<close-white title="Remove" @click="removeTag(tag)"></close-white>
				</span>
			</transition-group>
			<input type="text" v-model="addTagModel" @keyup="triggerChangeEvent" @change="addTag(null)" placeholder="Add tag" ref="addInputRef">
			<button-ex
				v-tooltip.top-center="{content: 'Show related tags', offset: 3, container: '.main-tags-container'}"
				:icon="{dir: 'left', component: expandedIcon, style: {width: '20px', marginTop: '-3px'}}"
				:radius="22"
				:class-attr="'hover-btn show-related-tags_btn'"
				:bg="'#fff'"
				:font-size="16"
				:color="'#0A273B'"
				:size="'md'"
				:padding="[7, 7, 7, 7]"
				:shadow="'0 7px 18px 0 rgba(0,0,0,0.09), 0 4px 8px 0 rgba(102,237,143,0.32)'"
				:border="{size: '2', color: '#66ED8F'}"
				@onclick="showRelatedTags"
			></button-ex>
		</div>
		<div v-show="showRelatedTagsWrap" class="related-tags-wrapper">
			<p v-if="relatedTagsLoading">Generating related tags
				<span>.</span>
				<span>.</span>
				<span>.</span>
			</p>
			<template v-else>
				<p v-if="relatedTags.length === 0">
					No related tags
				</p>
				<transition-group tag="div" name="slide-in" :style="{ '--total': relatedTags.length }">
					<template v-for="(tag, index) in relatedTags">
						<span
							:class="['every-tag', detectIfRelatedTagAdded(tag) && 'added-related-tag']"
							:style="{'--i': index}" v-if="showItems && index < relatedTags.length"
							:key="tag + index"
							@click="addTag(tag, index)"
						>{{tag}}</span>
					</template>
				</transition-group>
			</template>
		</div>
	</div>
</template>
<script>
	import Vue from "vue";
	import ButtonEx from 'components/shared/Button/index';
	import Icons from 'components/pic-snippet/Icons';
	import VTooltip from 'v-tooltip';
	import axios from 'axios';
	Vue.use(VTooltip);

	export default {
		name: 'add-snippet-tags',
		components: {
			ButtonEx,
			'close-white': Icons['close-white'],
		},
		props: {
			tags: {
				type: Array,
				default: () => [],
				required: true
			},
			picSnippet: {
				type: Object,
				required: true
			}
		},
		data () {
			return {
				relatedTags: [],
				showRelatedTagsWrap: false,
				relatedTagsLoading: false,
				showItems: false,
				expandedIcon: 'plus',
				modalIsOpened: true,
				addTagModel: '',
				alreadyExistedTag: false,
				indexesOfRelated: [],
				isIE11: !!window.MSInputMethodContext && !!document.documentMode,
			}
		},
		mounted() {
			this.moveTagInputIntoTheWrapper();
		},
		methods: {
			triggerChangeEvent (e) {
				//Triggering onChange event on IE 11
				if (e.keyCode === 13 && e.shiftKey || e.keyCode === 13 && this.isIE11) {
					this.addTag(null);
					e.preventDefault()
				}
			},
			detectIfRelatedTagAdded (tagName) {
				return this.indexesOfRelated.some(tag => tag.tagName === tagName) || this.$props.tags.some(tag => tag === tagName)
			},
			moveTagInputIntoTheWrapper () {
				this.$nextTick(() => {
					if (this.$refs.relativeTagRef) {
						this.$refs.relativeTagRef.$el.appendChild(this.$refs.addInputRef)
						this.$refs.addInputRef.focus();
					}
				})
			},
			calcItSelfWidth (tagName) { // need to give current width by size of text content
				const makeSpan = document.createElement("SPAN");
				makeSpan.innerText = tagName;
				makeSpan.style.fontSize = '13px';
				makeSpan.style.position = 'absolute';
				document.body.appendChild(makeSpan)
				const getWidth = makeSpan.getBoundingClientRect().width + 40;
				document.body.removeChild(makeSpan)

				return getWidth + 'px';
			},
			addTag (fromRelated, index) {
				const tags = [...this.$props.tags];
				const tagName = fromRelated || this.addTagModel;
				if (tags.indexOf(tagName) !== -1) {// show exited notification
					const alreadyExistedIndex = this.indexesOfRelated.findIndex(tag => tag.tagName === tagName);
					if (index !== undefined && alreadyExistedIndex !== -1) { // toggle click on the related tag item
						this.removeTag(tagName);
						return
					}
					this.alreadyExistedTag = true;
					this.addTagModel = '';
					setTimeout(() => { // hide exited notification
						this.alreadyExistedTag = false;
					}, 3000)
					return
				}
				if (index !== undefined || this.relatedTags.indexOf(tagName) !== -1) {
					this.indexesOfRelated.push({index, tagName});
				}
				tags.push(tagName);
				this.$store.commit('editor/UPDATE_SAVED_TEMPLATES', {id: this.$props.picSnippet.id, tags, changedOptions: 'tags'})
				this.addTagModel = '';
				this.moveTagInputIntoTheWrapper();
			},
			removeTag (tagName) {
				const indexTag = this.$props.tags.findIndex(tag => tag === tagName);
				const tags = [...this.$props.tags];
				if (indexTag !== -1) {
					const relatedIndex = this.indexesOfRelated.findIndex(tag => tag.tagName === tagName)
					if(relatedIndex !== -1) {
						this.indexesOfRelated.splice(relatedIndex, 1);
					}
					tags.splice(indexTag, 1);
					this.$store.commit('editor/UPDATE_SAVED_TEMPLATES', {id: this.$props.picSnippet.id, tags, changedOptions: 'tags'})
				}
			},
			showRelatedTags () {
				this.expandedIcon = (this.expandedIcon === 'plus') ? 'minus' : 'plus';
				this.showRelatedTagsWrap = !this.showRelatedTagsWrap;
				if (this.modalIsOpened) {
					this.relatedTagsLoading = true;
					axios.get(`/image/${this.$props.picSnippet.image_id}/tags`).then((res) => {
						this.relatedTagsLoading = false;
						this.modalIsOpened = false;
						this.relatedTags = res.data.result.tags.reduce((acc, item) => {
							acc.push(item.tag.en);
							return acc;
						}, []);
						this.$nextTick(() => {
							this.showItems = true;
						})
					});
				}
			}
		}
	}
</script>
<style scoped lang="scss">
	/*Animation of add and delete tag*/
	.list-enter-active, .list-leave-active {
		transition: all .4s;
		height: 30px;
		display: inline-flex !important;
		flex-shrink: 0;
		align-items: center;
		opacity: 1;
	}
	.list-enter, .list-leave-to{
		width: 0px !important;
		opacity:0;
		padding: 0 !important;
		margin: 0 !important;
		height: 30px;
		overflow: hidden;
	}

	/*Animation of related tags generating*/
	.slide-in {
		&-move {
			transition: opacity .5s linear, transform .5s ease-in-out;
		}
		&-leave-active {
			transition: opacity .4s linear, transform .4s cubic-bezier(.5,0,.7,.4); //cubic-bezier(.7,0,.7,1);
			transition-delay: calc( 0.01s * (var(--total) - var(--i)) );
		}
		&-enter-active {
			transition: opacity .5s linear, transform .5s cubic-bezier(.2,.5,.1,1);
			transition-delay: calc( 0.01s * var(--i) );
		}
		&-enter,
		&-leave-to {
			opacity: 0;
		}
	}

	.main-tags-container {
		.already-existed-tag {
			margin-bottom: 4px;
			padding: 6px;
			font-size: 14px;
			border-radius: 3px;
			color: #D62250;
		}
		.wrapper-tags {
			padding: 8px;
			border: 1px solid #ccc;
			border-radius: 3px;
			display: flex;
			position: relative;
			flex-wrap: wrap;
			align-items: center;
			display: flex;
			> div  {
				padding-right: 50px;
				max-height: 250px;
				overflow-x: hidden;
				width: 100%;
				display: flex;
				flex-wrap: wrap;
				&::-webkit-scrollbar {
					width: 2px;
				}

				&::-webkit-scrollbar-thumb {
					background: transparent;
					border-radius: 5px;
				}
				&::-webkit-scrollbar-track {
					box-shadow: inset 0 0 2px transparent;
					border-radius: 5px;
				}
			}
			& .added-tag {
				padding: 3px 10px;
				display: inline-flex;
				align-items: center;
				background: #0a273b;
				color: #fff;
				border-radius: 3px;
				font-size: 13px;
				margin-right: 5px;
				margin-bottom: 5px;
				white-space: nowrap;
				svg {
					width: 15px;
					margin-left: 5px;
					cursor: pointer;
				}
			}
			input {
				flex: 1;
				border: 0;
				outline: none;
				font-size: 14px;
				padding: 3px 10px;
				margin-bottom: 5px;
				height: 30px;
				width: 100%;
			}
			.show-related-tags_btn {
				position: absolute;
				right: 7px;
				@media screen and (-ms-high-contrast: active), (-ms-high-contrast: none) {
					top: 6px;
				}
			}
		}
		.related-tags-wrapper {
			padding: 5px;
			border: 1px solid #ccc;
			border-radius: 3px;
			margin-top: 5px;
			display: flex;
			position: relative;
			flex-wrap: wrap;
			align-items: center;
			max-height: 330px;
			overflow-x: hidden;
			&::-webkit-scrollbar {
				width: 2px;
			}

			&::-webkit-scrollbar-thumb {
				background: transparent;
				border-radius: 5px;
			}
			&::-webkit-scrollbar-track {
				box-shadow: inset 0 0 2px transparent;
				border-radius: 5px;
			}
			> p {
				text-align: center;
				font-size: 14px;
				width: 100%;
				> span {
					animation-name: blink;
					animation-duration: 1.4s;
					animation-iteration-count: infinite;
					animation-fill-mode: both;
					font-weight: 900;
					&:nth-child(2) {
						animation-delay: .2s;
					}
					&:nth-child(3) {
						animation-delay: .4s;
					}
				}
			}
			.every-tag {
				cursor: pointer;
				padding: 3px 10px;
				display: inline-block;
				background: #dcdcdc;
				color: #000;
				border-radius: 3px;
				font-size: 13px;
				margin-right: 5px;
				margin-bottom: 5px;
				padding-left: 10px;
				border: 1px solid transparent;
				&:hover {
					border: 1px solid #0a273b;
				}
			}
			.every-tag.added-related-tag {
				background: #0a273b;
				color: #fff;
			}
		}
	}

	@keyframes blink {
		0% {
			opacity: .2;
		}
		20% {
			opacity: 1;
		}
		100% {
			opacity: .2;
		}
	}
</style>
