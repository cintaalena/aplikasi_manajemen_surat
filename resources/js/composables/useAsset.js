import { usePage } from '@inertiajs/vue3'

export function useAsset() {
    const page = usePage()

    const asset = (path) => {
        const base = page.props.assetUrl ?? ''
        return base + '/' + path.replace(/^\//, '')
    }

    return { asset }
}
