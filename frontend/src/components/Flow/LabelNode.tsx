import clsx from 'clsx';
import { Button, TextInput } from 'flowbite-react';
import React, { memo, useEffect, useState } from 'react';
import { Handle } from 'reactflow';
import { DeleteModal } from '../DeleteModal';

export default memo(({ data, id }: any) => {
    const [edit, setEdit] = useState(false)
    const [dataState, setDataState] = useState(data)
    const [deleteOpen, setDeleteOpen] = useState(false)
//data.delete(id)
    useEffect(() => {
        setDataState(data)
    }, [edit])

    const deleteLabel = () => {
        data.delete(id)
        setDeleteOpen(false)
    }
    const cancel = () => {
        setEdit(!edit)
        setDataState(data)
    }
    const submit = (e: React.FormEvent) => {
        e.preventDefault()
        data.onChange(dataState, id).then((result: boolean) => {
            if (result) {
                setEdit(!edit)
            }
        })
    }
    return (
        <>
            <form className="group h-20" onSubmit={submit}>
                {edit ? <TextInput type="text" className="dark:text-black nodrag flex items-center justify-center h-full" value={dataState.label} onChange={(e) => setDataState({...dataState, label: e.target.value})} /> : <p className="flex items-center justify-center h-full">{data.label}</p>}
                <div className={clsx("group-hover:flex flex-wrap gap-2 pt-2", !edit && 'hidden', edit && 'flex')}>
                    <Button className={clsx('nodrag', edit ? 'inline' : 'hidden')} type="submit">Save</Button>
                    <Button color="gray" className={clsx('nodrag', edit ? 'inline' : 'hidden')} onClick={() => cancel()}>Cancel</Button>
                    <Button className={clsx('nodrag', edit ? 'hidden' : 'inline')} onClick={() => setEdit(!edit)}>Edit</Button>
                    <Button className='inline nodrag' color="failure" onClick={() => data.delete({id: id, data: dataState})}>Delete</Button>
                </div>
            </form>        </>
    );
});
