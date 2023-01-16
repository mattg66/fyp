export const fetcher = async (url: string) => {
    const res = await fetch(url)
    const json = await res.json()

    if (!res.ok) {
        return { status: false, json: json }
    }

    return { status: true, json: json }
}