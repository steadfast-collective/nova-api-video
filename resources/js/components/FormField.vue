<template>
    <default-field :field="field" :errors="errors" :show-help-text="showHelpText">
        <template slot="field">
            <span class="mr-4 form-file">
                <input
                    ref="fileField"
                    class="select-none form-file-input"
                    type="file"
                    :id="idAttr"
                    name="name"
                    @change="fileChange"
                    :disabled="uploading"
                    :accept="field.acceptedTypes"
                />
                <label :for="labelFor" class="select-none form-file-btn btn btn-default btn-primary">
                    <span v-if="uploading">{{ __('Uploading') }} ({{ uploadProgress }}%)</span>
                    <span v-else>{{ __('Choose File') }}</span>
                </label>
            </span>

            <span class="text-sm select-none text-90">
                {{ currentLabel }}
            </span>

            <p v-if="hasError" class="mt-2 text-xs text-danger">{{ firstError }}</p>
        </template>
    </default-field>
</template>

<script>
import { FormField, HandlesValidationErrors, Errors } from 'laravel-nova'

export default {
    mixins: [FormField, HandlesValidationErrors],

    props: ['resourceName', 'resourceId', 'field'],

    data: () => ({
        file: null,
        fileName: '',
        // removeModalOpen: false,
        // missing: false,
        // deleted: false,
        uploadErrors: new Errors(),
        videoFile: {
            file_name: '',
            file_type: '',
            size: 0,
            api_video_id: '',
            video_duration: 0,
        },
        uploading: false,
        uploadProgress: 0,
        numberOfChunks: 0,
        chunkCounter: 0,
        chunkSize: 25000000, // 25MB
        videoId: '',
        videoDuration: 0,
        url: null,
    }),

    mounted() {
        this.url = this.field.api_video_url

        // eslint-disable-next-line vue/no-mutating-props
        this.field.fill = (formData) => {
            let attribute = this.field.attribute

            if (this.file) {
                formData.append(attribute, this.fileName)
                formData.append('videoFile[' + attribute + '][file_name]', this.videoFile.file_name)
                formData.append('videoFile[' + attribute + '][file_type]', this.videoFile.file_type)
                formData.append('videoFile[' + attribute + '][size]', this.videoFile.size)
                formData.append('videoFile[' + attribute + '][api_video_id]', this.videoFile.api_video_id)
                formData.append('videoFile[' + attribute + '][video_duration]', this.videoFile.video_duration)
            }
        }
    },

    methods: {
        /**
         * Respond to the file change
         */
        fileChange(event) {
            let path = event.target.value
            let fileName = path.match(/[^\\/]*$/)[0]
            this.fileName = fileName
            this.file = this.$refs.fileField.files[0]

            this.getVideoDuration().then(() => {
                const start = 0
                this.chunkCounter = 0
                this.videoId = ''
                this.uploading = true
                this.$emit('file-upload-started')

                //upload the first chunk to get the videoId
                this.createChunk(this.videoId, start)
            })
        },

        createChunk(videoId, start) {
            this.chunkCounter++
            const chunkEnd = Math.min(start + this.chunkSize, this.file.size)
            const chunk = this.file.slice(start, chunkEnd)
            let chunkForm = new FormData()
            this.numberOfChunks = Math.ceil(this.file.size / this.chunkSize)

            if (videoId.length > 0) {
                // we have a videoId
                chunkForm.append('videoId', videoId)
            }

            chunkForm.append('file', chunk, this.fileName)

            //created the chunk, now upload it
            this.uploadChunk(chunkForm, start, chunkEnd)
        },

        uploadChunk(chunkForm, start, chunkEnd) {
            const blobEnd = chunkEnd - 1
            const contentRange = 'bytes ' + start + '-' + blobEnd + '/' + this.file.size

            const config = {
                onUploadProgress: (progressEvent) => {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total)
                    this.uploadProgress = Math.round(
                        ((this.chunkCounter - 1) / this.numberOfChunks) * 100 + percentCompleted / this.numberOfChunks
                    )
                },
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'Content-Range': contentRange,
                },
            }

            return axios.post(this.url, chunkForm, config).then((response) => {
                const data = response.data
                this.videoId = data.videoId
                start += this.chunkSize
                if (start < this.file.size) {
                    this.createChunk(this.videoId, start)
                } else {
                    this.videoFile.file_name = this.fileName
                    this.videoFile.file_type = this.file.type
                    this.videoFile.size = this.file.size
                    this.videoFile.api_video_id = this.videoId
                    this.videoFile.video_duration = this.videoDuration
                    this.uploadProgress = 0
                    this.uploading = false
                    this.$emit('file-upload-finished')
                }
            })
        },

        getVideoDuration() {
            return new Promise((resolve, reject) => {
                let video = document.createElement('video')
                video.preload = 'metadata'

                video.onloadedmetadata = () => {
                    window.URL.revokeObjectURL(video.src)

                    this.videoDuration = Math.floor(video.duration)

                    video.remove()

                    resolve()
                }

                video.src = URL.createObjectURL(this.file)
            })
        },
    },

    computed: {
        /**
         * Determine if the field has an upload error.
         */
        hasError() {
            return this.uploadErrors.has(this.fieldAttribute)
        },

        /**
         * Return the first error for the field.
         */
        firstError() {
            if (this.hasError) {
                return this.uploadErrors.first(this.fieldAttribute)
            }

            return null
        },

        /**
         * The current label of the file field.
         */
        currentLabel() {
            return this.fileName || this.__('no file selected')
        },

        /**
         * The ID attribute to use for the file field.
         */
        idAttr() {
            return this.labelFor
        },

        /**
         * The label attribute to use for the file field.
         */
        labelFor() {
            let name = this.resourceName

            if (this.relatedResourceName) {
                name += '-' + this.relatedResourceName
            }

            return `file-${name}-${this.field.attribute}`
        },
    },
}
</script>
