<!--
  - @copyright 2021 Daniel Kesselberg <mail@danielkesselberg.de>
  -
  - @author 2021 Daniel Kesselberg <mail@danielkesselberg.de>
  - @author 2023 Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<div class="alias-form">
		<form v-if="showForm"
			:id="formId"
			class="alias-form__form"
			@submit.prevent="updateAlias">
			<input v-model="changeName"
				type="text"
				class="alias-form__form__input"
				required>
			<input v-model="changeAlias"
				:disabled="alias.provisioned"
				type="email"
				class="alias-form__form__input"
				required>
		</form>
		<div v-else>
			<strong>{{ alias.name }}</strong> &lt;{{ alias.alias }}&gt;
		</div>

		<div class="alias-form__actions">
			<template v-if="showForm">
				<NcButton type="tertiary-no-background"
					:aria-label="t('mail', 'Update alias')"
					native-type="submit"
					:form="formId"
					:title="t('mail', 'Update alias')">
					<template #icon>
						<IconLoading v-if="loading" :size="20" />
						<IconCheck v-else :size="20" />
					</template>
				</NcButton>
			</template>
			<template v-else>
				<!-- Extra buttons -->
				<slot />

				<NcButton v-if="enableUpdate"
					type="tertiary-no-background"
					:aria-label="t('mail', 'Rename alias')"
					:title="t('mail', 'Show update alias form')"
					@click.prevent="showForm = true">
					<template #icon>
						<IconRename :size="20" />
					</template>
				</NcButton>
				<NcButton v-if="enableDelete && !alias.provisioned"
					type="tertiary-no-background"
					:aria-label="t('mail', 'Delete alias')"
					:title="t('mail', 'Delete alias')"
					@click.prevent="deleteAlias">
					<template #icon>
						<IconLoading v-if="loading" :size="20" />
						<IconDelete v-else :size="20" />
					</template>
				</NcButton>
			</template>
		</div>
	</div>
</template>

<script>
import { NcButton, NcLoadingIcon as IconLoading } from '@nextcloud/vue'
import IconDelete from 'vue-material-design-icons/Delete.vue'
import IconRename from 'vue-material-design-icons/Pencil.vue'
import IconCheck from 'vue-material-design-icons/Check.vue'

export default {
	name: 'AliasForm',
	components: {
		NcButton,
		IconRename,
		IconLoading,
		IconDelete,
		IconCheck,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
		alias: {
			type: Object,
			required: true,
		},
		enableUpdate: {
			type: Boolean,
			default: true,
		},
		enableDelete: {
			type: Boolean,
			default: true,
		},
		onUpdateAlias: {
			type: Function,
			default: async (aliasId, { alias, name }) => {},
		},
		onDelete: {
			type: Function,
			default: async (aliasId) => {},
		},
	},
	data() {
		return {
			changeAlias: this.alias.alias,
			changeName: this.alias.name,
			showForm: false,
			loading: false,
		}
	},
	computed: {
		formId() {
			return `alias-form-${this.alias.id}`
		},
	},
	methods: {
		/**
		 * Call alias update event handler of parent.
		 *
		 * @return {Promise<void>}
		 */
		async updateAlias() {
			this.loading = true
			await this.onUpdateAlias(this.alias.id, {
				alias: this.changeAlias,
				name: this.changeName,
			})
			this.showForm = false
			this.loading = false
		},
		/**
		 * Call alias deletion event handler of parent.
		 *
		 * @return {Promise<void>}
		 */
		async deleteAlias() {
			this.loading = true
			await this.onDelete(this.alias.id)
			this.loading = false
		},
	},
}
</script>

<style lang="scss" scoped>
$form-gap: 10px;

.alias-form {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: $form-gap;
	flex-wrap: wrap;
	width: 100%;

	&__form {
		display: flex;
		flex: 1 auto;
		gap: 10px; // Gap between inputs

		&--expand {
			// Prevent the submit button from being wrapped to the next line on normal sized screens
			flex-basis: calc(100% - 44px - $form-gap);
		}

		&__input {
			flex: 1 auto;
		}
	}

	&__actions {
		display: flex;
	}
}
</style>
